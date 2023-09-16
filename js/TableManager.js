import { $, $$ } from '/js/main.js'
import EventEmitter from '/js/EventEmitter.js'
import IDGenerator from '/js/IDGenerator.js'

export default class TableManager {

    constructor(table) {
        this.table = table
        this.selectedRow = null
        // Get row key from table attribute
        this.rowKey = this.table.getAttribute('use-key')
        if(this.rowKey){
            this.rowKey = this.rowKey.split(',').map( k => k.trim())
        }

        this.events = new EventEmitter()
        this.clickTimer = null
        this.isDoubleClicking = false
        this.IDGen = new IDGenerator(1000, false)

        this.table.addEventListener('click', e => {           
            if (this.isDoubleClicking) {
                this.isDoubleClicking = false
                return
            }

            this.clickTimer = setTimeout(() => {
                if (this.isDoubleClicking) {
                    this.isDoubleClicking = false
                    return
                }

                const selRow = this.getClickedRow(e.target)

                if(selRow){
                    this.setSelectedRow(selRow)
                }

                let actionBtn
                if(actionBtn = e.target.closest('[data-action]')){
                    this.events.emit('action', actionBtn.dataset.action, actionBtn)
                }
            }, 50)
        })

        this.table.addEventListener('dblclick', e => {
            e.preventDefault()
            
            if (this.clickTimer) {
                clearTimeout(this.clickTimer)
                this.clickTimer = null
            }

            this.isDoubleClicking = true
            setTimeout(() => {
                this.isDoubleClicking = false
            }, 50)

            const selRow = this.getClickedRow(e.target)
            if(selRow){
                this.setSelectedRow(selRow, false)
                this.events.emit('row-double-clicked', selRow)
            }
        })

        this.table.addEventListener('touchstart', e => {
            const selRow = this.getClickedRow(e.target)
            if(selRow){
                this.setSelectedRow(selRow, false)
    
                this.clickTimer = setTimeout(() => {
                    this.isDoubleClicking = true
                    this.events.emit('row-double-clicked', selRow)
                }, 750)
            }
        }, {passive: true})

        this.table.addEventListener('touchend', e => {
            if (this.clickTimer) {
                clearTimeout(this.clickTimer)
            }
        }, {passive: true})

        this.table.addEventListener('touchmove', e => {
            if (this.clickTimer) {
                clearTimeout(this.clickTimer)
            }
        }, {passive: true})
    }

    getClickedRow(target) {
        let tr = null

        if (target.tagName == 'TR') {
            tr = target
        } else {
            tr = target.closest('tr')
        }

        return (tr && tr.parentElement.tagName == 'TBODY') ? tr : null
    }

    listen(event, callback){
        this.events.listen(event, callback);
    }

    rowsCount(){
        this.setupTableBody(this.table)
        return this.table.tBodies[0].rows.length
    }

    setSelectedRow(row, withEvent) {
        if (withEvent == null) {
            withEvent = true
        }

        withEvent = !!withEvent

        if (this.selectedRow) {
            this.selectedRow.classList.remove('selected')
        }
        
        this.selectedRow = row
        if (row) {
            this.selectedRow.classList.add('selected')

            if (withEvent) {
                this.events.emit('row-selected', row)
            }
        }
    }

    getSelectedRow() {
        return this.selectedRow
    }

    findRows(searchFunc) {
        if(typeof searchFunc != 'function'){
            throw Error('Search function not provided')
        }
        this.setupTableBody(this.table)
        
        // Find cell indexes for searched columns
        let cols = this.getModelColumns()
        
        const rows = []
        Array.from(this.table.tBodies[0].rows).forEach(r => {
            const row = {}
            cols.forEach((col, i) => {
                // Ignore actions column
                if(col.name == 'actions'){
                    return
                }

                let value = r.cells[i].dataset.value
                let text = r.cells[i].textContent
    
                value = (value !== undefined)? value: text
                
                row[col.name] = {
                    value,
                    text
                }
            })

            // Don't pass
            if(searchFunc(row)){
                rows.push(row)
            }
        })

        return rows
    }

    getCellValue(row, col) {
        if(!row){
            return null
        }

        this.setupTableBody(this.table)
        let cellIndex = $(`thead [data-model='${col}']`, this.table).cellIndex

        let dataValue = row.cells[cellIndex].dataset.value
        let textValue = row.cells[cellIndex].textContent

        return (dataValue !== undefined)? dataValue: textValue
    }

    getCellText(row, col) {
        if(!row){
            return null
        }
        
        this.setupTableBody(this.table)
        let cellIndex = $(`thead [data-model='${col}']`, this.table).cellIndex

        let textValue = row.cells[cellIndex].textContent

        return textValue
    }

    getSelectedKey(){
        return this.getCellValue(this.selectedRow, this.rowKey)
    }

    setupTableBody() {
        let tBody = $('tbody', this.table)
        if (!tBody) {
            tBody = document.createElement('tBody')
            this.table.appendChild(tBody)
        }

        return tBody
    }

    buildTableRow(row, cols) {
        if(!row){
            return
        }

        if (!cols) {
            cols = this.getModelColumns(this.table)
        }

        let tr = document.createElement('tr')
        
        for (let c of cols) {
            // No need to check if column name (model) exists in the row because we're maybe using custom name to be handled by cellRenderCallback

            let td = document.createElement('td')
            if (c.class) {
                td.className = c.class
            }

            let display = row[c.name]

            if (c.value) {
                // Use value may contains comma separated column names
                let colNames = c.value.split(',').map(col => col.trim())
                if (colNames.length == 1) {
                    td.dataset.value = row[c.value]
                } else {
                    const colValues = colNames.reduce((acc, cur) => {
                        acc[cur] = row[cur]
                        return acc
                    }, {})
                    td.dataset.value = JSON.stringify(colValues)
                }
            }

            let display2 = undefined
            display2 = this.events.emit('cell-render', c.name, display, row, td)

            td.innerHTML = (display2 !== undefined) ? display2 : display

            tr.appendChild(td)
        }

        // If row key not specified by user or not in the row fields then use ID generator
        let rowKey = null

        if(this.rowKey.length){
            rowKey = this.rowKey.reduce((acc, k) => {
                acc.push(row[k])
                return acc
            }, []).join(',')
        }else{
            rowKey = this.IDGen.get()
        }

        tr.setAttribute('key', rowKey)

        this.events.emit('row-render', row, tr)

        return tr
    }

    getModelColumns() {
        const tHead = $$('thead th', this.table)

        if (!tHead) {
            return null
        }

        const cols = []

        for (let c of tHead) {
            if ('model' in c.dataset) {
                let useValue = c.getAttribute('use-value')

                if (useValue === '') {
                    useValue = c.dataset.model
                }

                cols.push({
                    value: useValue,
                    name: c.dataset.model,
                    class: c.dataset.class
                })
            }
        }

        return cols
    }

    clear(){
        const tBody = this.setupTableBody(this.table)
        tBody.innerHTML = ''
        this.selectedRow = null
    }
    
    renderTable(data, clear) {
        const tBody = this.setupTableBody(this.table)

        if (clear) {
            tBody.innerHTML = ''
            this.selectedRow = null
        }

        // Generate tbody rows
        const cols = this.getModelColumns(this.table)

        if (!(data instanceof Array)) {
            this.events.emit('render-error', 'Data must be an array')
            return false
        }

        data.forEach(row => {
            let tr = this.buildTableRow(row, cols)
            if(!tr){
                return
            }

            tBody.appendChild(tr)
        })

        return true
    }

    getRow(rowKey){
        const row = $(`tr[key="${rowKey}"]`, this.table.tBodies[0])
        return row
    }

    addRow(data) {
        // Add all rows in the data
        if (!(data instanceof Array)) {
            data = [data]
        }

        const tBody = this.setupTableBody(this.table)
        let tr = null
        data.forEach(row => {
            tr = this.buildTableRow(row)
            if(!tr){
                return
            }

            tBody.prepend(tr)
        })
        
        if(tr){
            this.setSelectedRow(tr)
        }

        return true
    }

    updateRow(data, oldKey) {
        // Update all rows in the data
        if (!(data instanceof Array)) {
            data = [data]
        }

        const tBody = this.setupTableBody(this.table)

        let tr = null
        data.forEach(row => {
            tr = this.buildTableRow(row)
            if(!tr){
                return
            }
    
            let oldRow = null
            if(oldKey){
                oldRow = this.getRow(oldKey)
            }else{
                oldRow = this.getRow(row[this.rowKey])
            }

            tBody.insertBefore(tr, oldRow)
            if(oldRow){
                oldRow.remove()
            }
        })

        if(tr){
            this.setSelectedRow(tr)
        }

        return true
    }

    removeRow(rowKeys) {
        // Remove all rows in the rowKeys
        if (!(rowKeys instanceof Array)) {
            rowKeys = [rowKeys]
        }

        this.setupTableBody(this.table)

        rowKeys.forEach(key => {
            const oldRow = this.getRow(key)
    
            if (oldRow == this.selectedRow) {
                this.selectedRow = null
            }
    
            oldRow.remove()
        })

        return true
    }
}