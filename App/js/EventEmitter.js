export default class EventEmitter {
    
    listeners = [];

    // Emit an event with multiple number of arguments
    emit(eventName, ...data){
        for(let {callback} of this.listeners.filter(l => l.name == eventName)){
            callback(...data);
        }
    }

    listen(name, callback){
        this.listeners.push({name, callback});
    }

    remove(eventName){
        this.listeners = this.listeners.filter(({name}) => name != eventName);
    }
}