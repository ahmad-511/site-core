import EventEmitter from '/js/EventEmitter.js';

export default class Cart{

    constructor(cartName ='myCart', fieldsMapper = {}, setAsGlobal = false){
        this.cartName = cartName;

        this.fieldsMapper = Object.assign(fieldsMapper, {
            id: 'id',
            description: 'description',
            quantity: 'quantity',
            price: 'price',
            notes: 'notes',
            url: 'url'
        });

        this.events = new EventEmitter();
        this.items = [];

        if(setAsGlobal){
            window[cartName] = this;
        }
    }

    listen(event, callback){
        this.events.listen(event, callback);
    }

    load(){
        this.items = JSON.parse(localStorage.getItem(this.cartName)??'[]');
        this.events.emit('loaded', this.items.length);
    }

    save(){
        localStorage.setItem(this.cartName, JSON.stringify(this.items));
        this.events.emit('saved');
    }

    clear(){
        this.items = [];
        this.save();
        this.events.emit('cleared');
    }

    checkFields(item){
        const err = [];

        if(!item[this.fieldsMapper.id]){
            err.push(`Cart item ${this.fieldsMapper.id} is required`);
        }

        if(!item[this.fieldsMapper.description]){
            err.push(`Cart item ${this.fieldsMapper.description} is required`);
        }

        if(!item[this.fieldsMapper.quantity]){
            err.push(`Cart item ${this.fieldsMapper.quantity} is required`);
        }

        if(item[this.fieldsMapper.price] == null){
            err.push(`Cart item ${this.fieldsMapper.price} is required`);
        }

        return err.join(',');
    }

    addItem(item){
        // Check the existence of cart fields
        let err;
        if((err = this.checkFields(item))){
            return err;
        }

        if(item[this.fieldsMapper.quantity] <= 0){
            item[this.fieldsMapper.quantity] = 1;
        }

        this.items.push(item);
        this.save();
        this.events.emit('item-added', this.items.length);

        return '';
    }

    getItem(id){
        return this.items.find(item => item[this.fieldsMapper.id] == id);
    }
    
    getAllItems(){
        return this.items;
    }

    count(){
        return this.items.length;
    }

    removeItem(id){
        if(!this.getItem(id)){
            return false;
        }

        this.items = this.items.filter(item => item[this.fieldsMapper.id] != id);
        this.save();
        this.events.emit('item-removed', this.items.length);
        return true;
    }

    setQuantity(id, quantity){
        const item = this.getItem(id);

        if(!item){
            return false;
        }

        if(quantity <= 0){
            return this.removeItem(id);
        }

        item[this.fieldsMapper.quantity] = quantity;
        this.save();
        this.events.emit('quantity-changed', this.items.length);
        return item;
    }

    setPrice(id, price){
        const item = this.getItem(id);

        if(!item){
            return false;
        }

        item[this.fieldsMapper.price] = price;
        this.save();
        this.events.emit('price-changed', this.items.length);
        return item;
    }

    getSubTotal(){
        return this.items.reduce((prev, curr) => prev += curr[this.fieldsMapper.price] * curr[this.fieldsMapper.quantity], 0)
    }
}