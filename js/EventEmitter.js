export default class EventEmitter {
    constructor() {
        this.listeners = []
    }

    // Emit an event with multiple number of arguments
    emit(eventName, ...data) {
        let lastReturn = undefined

        for (let { callback } of this.listeners.filter(l => l.name == eventName)) {
            const run = callback(...data)

            if(run !== undefined){
                lastReturn = run
            }
        }

        return lastReturn
    }

    listen(name, callback) {
        this.listeners.push({ name, callback })
    }

    remove(eventName) {
        this.listeners = this.listeners.filter(({ name }) => name != eventName)
    }
}