export default class Viewer{
    constructor(container){
        container = container || document.body

        if(!(container instanceof HTMLElement)){
            throw new Error('Viewer: Container must be of type HTMLElement')
        }

        this.viewer = document.createElement('aside')
        this.viewer.className = `viewer`
        this.viewer.setAttribute('hidden', true)

        const buttons = document.createElement('div')
        buttons.className = 'viewer-buttons'

        this.download = document.createElement('a')
        this.download.textContent = 'Download'
        this.download.className = 'viewer-btn viewer-download'
        
        this.close = document.createElement('button')
        this.close.className = 'viewer-btn viewer-close'
        this.close.innerText = 'Close'
        this.close.addEventListener('click', e => {
            this.viewer.setAttribute('hidden', true)
        })

        buttons.appendChild(this.close)
        buttons.appendChild(this.download)
        this.media = null
        this.viewer.appendChild(buttons)
        container.appendChild(this.viewer)
    }

    load(url, type, downloadName){
        type = type.toLocaleLowerCase()

        if(this.media){
            this.media.remove()
        }

        switch(true){
            case type.startsWith('image/'):
                this.media = document.createElement('img')
                this.media.className = 'viewer-content'
                break
                
            case type.endsWith('/pdf'):
                this.media = document.createElement('iframe')
                this.media.title = downloadName
                this.media.className = 'viewer-content'
                break
                
            default:
                this.media = document.createElement('p')
                this.media.textContent = 'File type not supported'
                this.media.className = 'not-supported'
        }

        this.media.src = url
        this.viewer.appendChild(this.media)

        this.download.href = url
        this.download.download = downloadName
    }

    show(){
        this.viewer.removeAttribute('hidden')
    }

    hide(){
        this.viewer.setAttribute('hidden', true)
    }
}