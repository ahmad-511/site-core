import { $$ } from "/js/main.js"
import EventEmitter from "/js/EventEmitter.js"

export default class StarRating {
    constructor(container, valueField, max = 5, value = 0, star = '☆', checkedStar = '★') {
        if (!(container instanceof HTMLElement)) {
            console.log('HTMLElement is expected in container argument')
        }

        this.container = container

        if (valueField){
            if (!(valueField instanceof HTMLInputElement)) {
                console.log('HTMLInputElement required')
            }
        }else{
            valueField = null
        }

        this.valueField = valueField
        this.max = max
        this.value = value
        this.star = star
        this.checkedStar = checkedStar
        this.starsContainer = null
        this.freezed = false
        this.events = new EventEmitter()
        this.render()
    }

    listen(event, callback){
        this.events.listen(event, callback)
    }

    render() {
        this.starsContainer = this.container.appendChild(document.createElement('p'))
        this.starsContainer.className = 'stars-container'

        for (let i = 0; i < this.max; i++) {
            const s = document.createElement('span')
            s.innerHTML = this.star
            s.className = 'star'
            s.dataset.value = i + 1

            this.starsContainer.appendChild(s)
        }

        this.setValue(this.value)

        this.starsContainer.addEventListener('click', e => {
            if (this.freezed) {
                return
            }

            const elem = e.target
            const star = elem.closest('span.star')

            if (!star) {
                return
            }

            let value = star.dataset.value

            // Clear value if the same star clicked twice
            if(this.value == value){
                value = 0
            }

            this.setValue(value)
        })
    }

    setValue(value) {
        this.value = value

        if(this.valueField){
            this.valueField.value = value
        }

        const stars = $$('.star', this.starsContainer)
        stars.forEach(star => {
            star.innerHTML = this.star
            star.className = 'star'

            if (value >= star.dataset.value) {
                star.innerHTML = this.checkedStar
                star.classList.add('checked')
            }
        });

        this.events.emit('change', this.value)
    }

    getValue(){
        return this.valueField.value
    }

    freeze(isFreezed = true) {
        this.freezed = isFreezed
        this.starsContainer.classList.toggle('freezed', isFreezed)
    }

}