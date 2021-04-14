export default class EventEimitter {
    
    listeners = [];

    emit(eventName, ...data){
        for(let {callback} of this.listeners.filter(l => l.name == eventName)){
            return callback(...data);
        }
    }

    listen(name, callback){
        this.listeners.push({name, callback});
    }

    remove(eventName){
        this.listeners = this.listeners.filter(({name}) => name != eventName);
    }
}