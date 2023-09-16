import friendlySize from "/js/friendlySize.js"
import EventEmitter from "/js/EventEmitter.js"
import IDGenerator from "/js/IDGenerator.js"

export default class FileDrop {
    constructor(container, types=[], maxCount = 0, maxSize = 0, keyPrefix = 'FL_'){
        if (!(container instanceof HTMLElement)) {
            console.log('HTMLElement required')
        }

        this.container = container
        this.types = types
        this.maxCount = maxCount
        this.maxSize = maxSize
        this.keyPrefix = keyPrefix
        
        this.events = new EventEmitter()
        this.IDGen = new IDGenerator()
        this.files = []

        this.container.classList.add('drop-area')

        this.container.addEventListener('dragover', e => {
            e.preventDefault()
            this.container.classList.add('drop-area-active')
        })

        this.container.addEventListener('dragenter', e => {
            this.container.classList.add('drop-area-active')
        })

        this.container.addEventListener('dragleave', e => {
            this.container.classList.remove('drop-area-active')
        })
        
        this.container.addEventListener('drop', e => {
            e.preventDefault()
            this.container.classList.remove('drop-area-active')

            this.addFiles(e.dataTransfer.files)
        })
    }

    listen(event, callback){
        this.events.listen(event, callback)
    }

    addFiles(files){
        // Convert file list to array
        if(files instanceof FileList){
            files = [...files]
        }

        const errors = []
        files.forEach(file => {
            errors.push(...this.addFile(file))
        })

        this.events.emit('completed', errors, this.files)
    }

    addFile(file){
        const errors = []
        const isHandled = !(file instanceof File)

        // Check file type
        if(!this.types.includes(file.type.toLowerCase())){
            errors.push(`Not allowed file type (${file.type})`)
        }

        // Check max file size
        if (this.maxSize && file.size > this.maxSize * 1024 * 1024){
            errors.push(`Exceeded max file size (${friendlySize(this.maxSize)})`)
        }
        
        // Check max file count
        if (this.maxCount && this.getCount() >= this.maxCount){
            errors.push(`Exceeded max files count (${this.maxCount})`)
        }

        if(errors.length){
            this.events.emit('error', errors, file)
        }
        
        // Allow handled files to be added even with error (so user can decide to delete or to keep them)
        if(!errors.length || isHandled){
            // Adding custom key property (if not exists) to help locating the file in the files array (as index may change)
            file.key = file.key??`${this.keyPrefix}${this.IDGen.get()}`
            
            // Mark the file as handled when it's not a real file api (this will be used as place holder for files already uploaded)
            file.isHandled = isHandled

            this.files.push(file)
            this.events.emit('file-added', file)
        }
        
        return errors
    }

    getFiles(onlyNew = false){
        if(onlyNew){
            return this.files.filter(file => !file.isHandled)
        }

        return this.files
    }

    getFile(key){
        const file = this.files.find(file => file.key == key)
        if(!file){
            this.events.emit('error', ['File not found'], key)
            return
        }
        
        return file
    }

    removeFile(key){
        // Find the file by key
        const file = this.getFile(key)
        if(!file){
            // Event already emitted
            return
        }

        this.files = this.files.filter(file => file.key != key)
        this.events.emit('file-removed', key, file)
    }

    clear(){
        this.files = []
        this.events.emit('files-cleared')
    }

    getCount(){
        return this.files.length
    }

    getStats(){
        const stats = {
            count: this.getCount(),
            totalSize: 0,
            fileTypes: {}
        }

        this.files.forEach(file => {
            stats.totalSize += file.size
            if(!stats.fileTypes[file.type]){
                stats.fileTypes[file.type] = 1
            }else{
                stats.fileTypes[file.type]++
            }
        })

        return stats
    }
}