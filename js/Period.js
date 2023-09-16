import plural from '/js/plural.js'

export function datePeriod(date, pastAdverb = 'ago', futureAdverb = 'left'){
    const dd = new Date(date).setHours(0, 0, 0, 0)
    const cd = new Date().setHours(0, 0, 0, 0)
    let days = Number.parseInt((dd - cd)/(24*60*60*1000))

    let isPast = false
    let period = ''
    let count = 0
    
    if(days < 0){
        isPast = true
    }

    days = Math.abs(days)

    if(days >= 365){
        period = 'Year'
        count = days / 365
    
    }else if(days >= 30){
        period = 'Month'
        count = days / 30
    
    }else if(days >= 7){
        period = 'Week'
        count = days / 7

    }else if(days > 1) {
        period = 'Day'
        count = days

    }else if(days == 1) {
        period = isPast?'Yesterday': 'Tomorrow'
        count = days

    }else{
        period = 'Today'
        count = days
    }

    count = Math.floor(count)
    
    if(count > 1){
        period = `${count} ${plural(period, count)} ${isPast?pastAdverb: futureAdverb}` 
    }else if(period){
        period = `${plural(period, count)} ${isPast?pastAdverb: futureAdverb}`
    }
    
    return period
}
