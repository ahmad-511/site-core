import {$, $$} from '/App/js/main.js';
import EventEmitter from "/App/js/EventEmitter.js";

export default class TableManager {

    constructor(table){
        this.table = table;
        this.selectedRow = null;

        this.events = new EventEmitter();
        this.clickTimer = null;
        this.isDoubleClicking = false;

        this.table.addEventListener('click', e => {
            if(this.isDoubleClicking){
                this.isDoubleClicking = false;
                return;
            }
            
            this.clickTimer = setTimeout(() => {
                if(this.isDoubleClicking){
                    this.isDoubleClicking = false;
                    return;
                }

                const selRow = this.getSelectedRow(e.target);

                this.setSelectedRow(selRow);
            }, 50);
        });

        this.table.addEventListener('dblclick', e => {
            if(this.clickTimer){
                clearTimeout(this.clickTimer);
                this.clickTimer = null;
            }

            this.isDoubleClicking = true;
            setTimeout(() => {
                this.isDoubleClicking = false;
            }, 50)

            const selRow = this.getSelectedRow(e.target);
            this.setSelectedRow(selRow, false);

            this.events.emit('row-double-clicked', selRow);
        });

        this.table.addEventListener('touchstart', e => {
            const selRow = this.getSelectedRow(e.target);
            this.setSelectedRow(selRow, false);

            this.clickTimer = setTimeout(() => {
                this.isDoubleClicking = true;
                this.events.emit('row-double-clicked', selRow);
            }, 750);
        });

        this.table.addEventListener('touchend', e => {
            if(this.clickTimer){
                clearTimeout(this.clickTimer);
            }
        });

        this.table.addEventListener('touchmove', e => {
            if(this.clickTimer){
                clearTimeout(this.clickTimer);
            }
        });
    }

    setSelectedRow(row, withEvent){
        if(withEvent == null){
            withEvent = true;
        }

        withEvent = !!withEvent;

        if(row){
            if (this.selectedRow) {
                this.selectedRow.classList.remove('selected');
            }
            
            this.selectedRow = row;
            this.selectedRow.classList.add('selected');

            if(withEvent){
                this.events.emit('row-selected', row);
            }
        }
    }

    getSelectedRow(target) {
        let tr = null;

        if (target.tagName == 'TR') {
            tr = target;
        }else{
            tr = target.closest('tr');
        }

        return (tr && tr.parentElement.tagName == 'TBODY')?tr: null;
    }

    getCellValue(rowIndex, col) {
        const tBody = this.setupTableBody(this.table);
        let cellIndex = $(`thead [data-model='${col}']`, this.table).cellIndex;

        let dataValue = tBody.rows[rowIndex].cells[cellIndex].dataset.value;
        let textValue = tBody.rows[rowIndex].cells[cellIndex].textContent;

        return dataValue !== undefined?dataValue: textValue;
    }

    setupTableBody() {
        let tBody = $('tbody', this.table);
        if (!tBody) {
            tBody = document.createElement('tBody');
            this.table.appendChild(tBody);
        }

        return tBody;
    }

    buildTableRow(row, cols) {
        if(!cols){
            cols = this.getModelColumns(this.table);
        }

        let tr = document.createElement('tr');

        for (let c of cols) {
            // No need to check if column name (model) exists in the row because we're maybe using custom name to be handled by cellRenderCallback

            let td = document.createElement('td');
            if(c.class){
                td.className = c.class;
            }

            let display = row[c.name];

            if(c.useValue){
                td.dataset.value = display;
            }

            let display2 = undefined;
            display2 = this.events.emit('cell-render', c.name, display, row);

            td.innerHTML = (display2 !== undefined)?display2: display;
            tr.appendChild(td);
        }

        return tr;
    }

    getModelColumns() {
        const tHead = $$('thead th', this.table);
        
        if (!tHead) {
            return null;
        }

        const cols = [];

        for (let c of tHead) {
            if ('model' in c.dataset) {
                cols.push({
                    name: c.dataset.model,
                    class: c.dataset.class,
                    useValue: c.getAttribute('use-value') !== null
                });
            }
        }

        return cols;
    }

    renderTable(data, clear) {
        const tBody = this.setupTableBody(this.table);
        
        if (clear) {
            tBody.innerHTML = '';
        }
        
        // Generate tbody rows
        const cols = this.getModelColumns(this.table);

        for (let row of data) {
            let tr = this.buildTableRow(row, cols);

            tBody.appendChild(tr);
        }
    }

    addRow(data) {
        if(!data){
            return false;
        }

        const tBody = this.setupTableBody(this.table);

        const tr = this.buildTableRow(data[0]);

        tBody.prepend(tr);
        this.setSelectedRow(tr);

        return true;
    }

    updateRow(data, rowIndex) {
        if(!data){
            return false;
        }

        const tBody = this.setupTableBody(this.table);

        const tr = this.buildTableRow(data[0]);

        tBody.insertBefore(tr, tBody.rows[rowIndex]);
        tBody.deleteRow(rowIndex + 1);

        this.setSelectedRow(tr);

        return true;
    }

    removeRow(rowIndex) {
        const tBody = this.setupTableBody(this.table);

        if(tBody.rows[rowIndex] == this.selectedRow){
            this.selectedRow = null;
        }

        tBody.deleteRow(rowIndex);
        
        return true;
    }
}