export default function friendlySize(size){
    const units = ['TB', 'GB', 'MB', 'KB', 'B']
    let multiplier = Math.pow(1024, units.length - 1)
    
    for(const unit of units) {
        const tmp = size / multiplier
        if(tmp >= 1){
            return `${Math.round((tmp + Number.EPSILON) * 100) / 100}${unit}`
        }

        multiplier /= 1024
    }
    return size
}