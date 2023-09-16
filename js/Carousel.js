import {$, $$} from '/js/main.js';

export default class Carousel {
    constructor(container, stepDuration = 300){
        this.container = container;
        this.stepDuration = stepDuration;
        this.isPlaying = false;
        this.playSpeed = 0;
        this.isReversePlay = false;
        this.playTimeout = null;
        this.isFullScreen = null;

        this.setSpeed(this.stepDuration);
        this.isFullScreen = this.container.classList.contains('full-screen');

        this.stripe = $('.carousel-stripe', this.container);

        this.previousButton = $('.previous', this.container);
        if(this.previousButton){
            this.previousButton.addEventListener('click', () => {
                this.stop();
                this.previous();
            });
        }

        this.nextButton = $('.next', this.container);
        if(this.nextButton){
            this.nextButton.addEventListener('click', () => {
                this.stop();
                this.next();
            });
        }

        this.closeButton = $('.close', this.container);
        if(this.closeButton){
            this.closeButton.addEventListener('click', () => {
                this.fullScreen(false);
            });
        }
    }

    setSpeed(speed = 300){
        this.container.style.setProperty('--STEP_DURATION', `${speed/1000}s`);
    }

    next(){
        this.updatePosition('next');
    }

    previous(){
        this.updatePosition('previous');
    }

    fullScreen(isFullScreen){
        this.isFullScreen = isFullScreen;
        this.container.classList.toggle('full-screen', isFullScreen);
    }

    play(speed = 300, reverse = false){
        this.isPlaying = true;
        this.playSpeed = speed;
        this.isReversePlay = reverse;

        if(reverse){
            this.previous();
        }else{
            this.next();
        }
    }

    stop(){
        this.isPlaying = false;
        this.playSpeed = 0;
        this.isReversePlay = false;
        if(this.playTimeout){
            clearTimeout(this.playTimeout);
        }
    }

    setCurrent(item){
        if(item == null || this.stripe.firstElementChild == item){
            return;
        }

        const itemsCount = this.stripe.childElementCount;
        const pos = Array.from($$('.carousel-item', this.stripe)).indexOf(item);
        // Chose the lesser steps needed to move the item to the first position
        if(pos < itemsCount / 2){
            // Append first element until we get the item in the first position
            while(this.stripe.firstElementChild != item){
                this.stripe.append(this.stripe.firstElementChild)
            }
        }else{
            // Prepend last element until we get the item in the first position
            while(this.stripe.firstElementChild != item){
                this.stripe.prepend(this.stripe.lastElementChild)
            }
        }
    }

    updatePosition(direction){
        if(direction == 'next'){
            this.stripe.classList.add('animate');
            this.container.style.setProperty('--STEP', this.isReversePlay?-1: 1);

            setTimeout(() => {
                this.stripe.classList.remove('animate');
                this.stripe.append(this.stripe.firstElementChild);
                this.container.style.setProperty('--STEP', 0);
                
                if(this.isPlaying){
                    this.playTimeout = setTimeout(() => {
                        this.next();
                    }, this.playSpeed);
                }
            }, this.stepDuration);
        }else{
            this.stripe.classList.remove('animate');
            this.container.style.setProperty('--STEP', 1);
            this.stripe.prepend(this.stripe.lastElementChild);

            setTimeout(() => {
                this.stripe.classList.add('animate');
                this.container.style.setProperty('--STEP', 0);

                if(this.isPlaying){
                    this.playTimeout = setTimeout( () => {
                        this.previous();
                    }, this.playSpeed + this.stepDuration);
                }
            }, 0);
        }
    }
}