export default class IDGenerator {
    constructor(seed = 0, useSession = false) {
        if (useSession) {
            this.seed = localStorage.getItem('IDGenerator-Seed') || 0;
        } else {
            this.seed = seed;
        }
    }

    get() {
        this.seed++;

        if (this.useSession) {
            localStorage.setItem('IDGenerator-Seed', this.seed);
        }

        return this.seed;
    }

    reset() {
        this.seed = 0;

        if (this.useSession) {
            localStorage.setItem('IDGenerator-Seed', this.seed);
        }
    }
}