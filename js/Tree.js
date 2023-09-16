import { $, $$ } from '/js/main.js';
import EventEmitter from "/js/EventEmitter.js";

export default class Tree {
    constructor(container, data, childIdCol, parentIdCol, displayCol, valueCol) {
        this.container = container;
        this.data = data;
        this.childIdCol = childIdCol;
        this.parentIdCol = parentIdCol;
        this.displayCol = displayCol;
        this.valueCol = valueCol;
        this.displayField = null;
        this.valueField = null;
        this.searchField = null;
        this.selectedElement = null;
        this.selectedData = null;
        this.selectedPath = null;
        this.pathSeparator = ' ► ';
        this.selectedIndex = -1;
        this.itemsCount = 0;

        this.events = new EventEmitter();

        // Handling mouse selection
        this.container.addEventListener('click', e => {
            if (this.setSelectedElement(this.getSelectedElement(e.target))) {
                this.updateLinkedFields(this.selectedElement.dataset.path, this.selectedElement.dataset.value);

                this.events.emit('item-selected', this.selectedElement, this.selectedData);
            }
        });
    }

    listen(event, callback){
        this.events.listen(event, callback);
    }

    setDisplayField(elem) {
        if (!(elem instanceof HTMLElement)) {
            console.log('HTMLElement required');
        }

        this.displayField = elem;
    }

    setValueField(elem) {
        if (!(elem instanceof HTMLElement)) {
            console.log('HTMLElement required');
        }

        this.valueField = elem;
    }

    setSearchField(elem) {
        if (!(elem instanceof HTMLInputElement)) {
            console.log('HTMLInputElement required');
        }

        this.searchField = elem;

        this.searchField.addEventListener('input', e => {
            this.search(e.target.value);
        });

        // Handling keyboard selection
        this.displayField.addEventListener('keydown', e => {
            let isChanged = false;

            switch (e.code) {
                case 'ArrowUp':
                    this.selectedIndex--;

                    if (this.selectedIndex < 0) {
                        this.selectedIndex = 0;
                    }

                    this.setSelectedIndex(this.selectedIndex);
                    isChanged = true;
                    break;

                case 'ArrowDown':
                    this.selectedIndex++;

                    if (this.selectedIndex > this.itemsCount - 1) {
                        this.selectedIndex = this.itemsCount - 1;
                    }

                    this.setSelectedIndex(this.selectedIndex);
                    isChanged = true;
                    break;

                case 'Tab':
                    if (this.selectedElement) {
                        this.selectedElement.click();
                    }
                    break;

                case 'Enter':
                    if (this.selectedElement) {
                        this.selectedElement.click();
                    }

                    isChanged = true;
                    break;
            }

            if (isChanged) {
                e.preventDefault();
            }
        });
    }

    setSelectedElement(elem) {
        if (!elem) {
            this.selectedElement = null;
            this.selectedData = null;
            this.selectedPath = null;
            this.selectedIndex = -1;

            return false;
        }

        if (this.selectedElement) {
            this.selectedElement.classList.remove('selected');
        }

        this.selectedElement = elem;

        this.selectedElement.classList.add('selected');
        this.selectedData = this.data.find(i => i[this.childIdCol] == this.selectedElement.dataset.id);
        this.selectedPath = this.selectedElement.dataset.path;
        this.selectedIndex = this.selectedElement.dataset.index;

        return true;
    }

    getSelectedElement(target) {
        if (target.tagName == 'LI') {
            return target;
        }

        return target.closest('li');
    }

    getItemData(id) {
        return this.data.find(i => i[this.childIdCol] == id);
    }

    setSelectedIndex(index) {
        const elem = $(`[data-index="${index}"]`, this.container);

        return this.setSelectedElement(elem);
    }

    render(data, searchStr) {
        this.container.innerHTML = '';
        this.selectedElement = null;
        this.selectedData = null;
        this.selectedPath = null;
        this.selectedIndex = -1;

        // Static counter for LIs
        this.render.index = 0;

        this.data = data;
        this.buildUL(data, this.container, 0, searchStr);

        this.itemsCount = data.length;

        if (searchStr) {
            this.displaySearchResult()
        }
    }

    buildUL(data, container, parentId, searchStr) {
        const children = data.filter(i => i[this.parentIdCol] == parentId);

        if (children.length == 0) {
            return;
        }

        container.dataset.isParent = true;
        let parentPath = container.dataset.path || '';

        container = container.appendChild(document.createElement('ul'));

        for (let i of children) {
            const span = document.createElement('span');
            const li = document.createElement('li');
            const path = `${parentPath}${parentPath ? this.pathSeparator : ''}${i[this.displayCol]}`;

            let display = i[this.displayCol];
            let value = i[this.valueCol];

            span.textContent = display;
            li.appendChild(span);
            li.dataset.display = display;
            li.dataset.value = value;
            li.dataset.id = i[this.childIdCol];
            li.dataset.index = this.render.index++;
            li.dataset.path = path;
            li.title = path;
            li.dataset.isParent = false;

            // Search
            if (searchStr && i[this.displayCol].toUpperCase().indexOf(searchStr.toUpperCase()) > -1) {
                li.dataset.match = true;
            }

            container.appendChild(li);

            this.buildUL(data, li, i[this.childIdCol], searchStr);
        }
    }

    displaySearchResult() {
        // Display result in separate container
        let ul = document.createElement('ul');

        $$('li[data-match="true"]', this.container).forEach((li, i) => {
            ul.appendChild(li);
        });

        // Reindex LIs (with their children)
        $$('li', ul).forEach((li, i) => {
            li.dataset.index = i;
            this.itemsCount = i + 1;
        });

        this.container.innerHTML = '';
        this.container.appendChild(ul);
    }

    add(item) {
        const parentId = item.parent_id;
        let parentUL = null;
        let parentPath = '';

        if (parentId == 0) {
            parentUL = $('ul', this.container);
        } else {
            // Finding parent LI item in the elements tree
            const parentLI = $(`li[data-id='${parentId}']`, this.container);

            if (!parentLI) {
                console.log(`Can't find parent with ID ${parentId}`);
                return;
            }

            parentPath = parentLI.dataset.path || '';

            // Get parent UL for the new item
            parentUL = $(`ul`, parentLI);

            if (!parentUL) {
                parentUL = parentLI.appendChild(document.createElement('ul'));
                parentLI.dataset.isParent = true;
            }
        }

        // Add the new item to the array
        this.data.push(item);

        const li = document.createElement('li');


        li.textContent = item[this.displayCol];
        li.dataset.value = item[this.valueCol];
        li.dataset.id = item[this.childIdCol];
        //li.dataset.index = index++;
        li.dataset.path = `${parentPath}${this.pathSeparator}${item[this.displayCol]}`;
        li.dataset.isParent = false;

        parentUL.appendChild(li);
    }

    remove(item) {
        const childId = item[this.childIdCol];

        // Finding the item in the array
        const curItemIndex = this.data.findIndex(i => i[this.childIdCol] == childId);

        if (!curItemIndex === -1) {
            console.log(`Can't find item with ID ${childId}`);
            return;
        }

        // Finding item in the elements tree
        const curItem = $(`li[data-id='${childId}']`, this.container);

        // remove item from elements tree
        if (curItem) {
            curItem.remove();

            if (this.selectedData && this.selectedData[this.childIdCol] == childId) {
                this.selectedElement = null;
                this.selectedData = null;
                this.selectedPath = null;
            }
        }

        const parentId = this.data[curItemIndex][this.parentIdCol];

        // remove item from the data array
        this.data = this.data.splice(curItemIndex, 1);

        // Update the parent's (isParent) attribute
        const parentItem = $(`li[data-id='${parentId}']`, this.container);

        const hasChildren = this.data.find(i => i[this.childIdCol] == parentId) != undefined;
        parentItem.dataset.isParent = hasChildren;
    }

    update(item) {
        const childId = item[this.childIdCol];

        // Finding the item in the array
        const curItemIndex = this.data.findIndex(i => i[this.childIdCol] == childId);

        if (!curItemIndex === -1) {
            console.log(`Can't find item with ID ${childId}`);
            return;
        }

        let curItem = this.data[curItemIndex];

        // remove item from elements tree
        this.remove(curItem);

        // Add the updated item
        this.add(item);

        // Update data array
        this.data[curItemIndex] = item;
    }

    getValues() {
        return this.data.map(i => i[this.valueCol]);
    }

    search(str) {
        this.render(this.data, str);
    }

    updateLinkedFields(display, value) {
        if (this.displayField) {
            if (this.displayField instanceof HTMLInputElement) {
                this.displayField.value = display;
            } else {
                this.displayField.textContent = display;
            }
        }

        if (this.valueField) {
            if (this.valueField instanceof HTMLInputElement) {
                this.valueField.value = value;
            } else {
                this.valueField.textContent = value;
            }
        }
    }
}