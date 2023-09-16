import EventEmitter from "/js/EventEmitter.js";

function buildActionButtons(actions) {
    const actionContainer = document.createElement('div');

    actions.forEach(action => {
        const btn = document.createElement('button');
        btn.name = action.name;
        btn.textContent = action.text || action.name;
        btn.type = action.isDefault ? 'submit' : 'button';
        btn.className = action.className;

        actionContainer.appendChild(btn);
    });

    return actionContainer;
}

export class Action {
    constructor(name, text, isDefault, className, data) {
        this.name = name;
        this.text = text;
        this.isDefault = isDefault;
        this.className = className;
        this.data = data;
    }
}

export default class Prompt {
    constructor(description, actions) {
        this.actions = actions;
        this.events = new EventEmitter();

        const dvContainer = document.createElement('aside');
        const frmPrompt = document.createElement('form');
        const btnClose = document.createElement('span');
        const dvDescription = document.createElement('div');
        const dvActionBar = buildActionButtons(actions);

        dvContainer.className = 'prompt-modal';
        frmPrompt.className = 'prompt-form';
        btnClose.className = 'prompt-close';
        dvDescription.className = 'prompt-description';
        dvActionBar.className = 'prompt-action-bar';

        dvContainer.tabIndex = 0;
        frmPrompt.tabIndex = 0;
        btnClose.textContent = '✖';
        dvDescription.innerHTML = description;

        dvContainer.addEventListener('focus', e => { this.prompt.focus() });
        frmPrompt.addEventListener('submit', e => {
            e.preventDefault()
        });

        // Supporting keyboard common behavior
        frmPrompt.addEventListener('keydown', e => {
            if (e.code == 'Escape') {
                this.close();
            }

            if (e.code == 'Enter') {
                // This will trigger the submit event (submit method wont do that)
                //e.target.requestSubmit() is not supported by Safari mobile
                dvActionBar.querySelector("[type='submit']").click();
            }
        });

        btnClose.addEventListener('click', e => { this.close() });

        dvActionBar.addEventListener('click', e => {
            if (e.target instanceof HTMLButtonElement) {
                const action = this.actions.find(action => action.name == e.target.name);
                this.events.emit('action', action, frmPrompt);

                setTimeout(() => {
                    this.close(e.target.name);
                }, 10);
            }
        });

        frmPrompt.appendChild(btnClose);
        frmPrompt.appendChild(dvDescription);
        frmPrompt.appendChild(dvActionBar);
        dvContainer.appendChild(frmPrompt);

        this.container = dvContainer;
        this.prompt = frmPrompt;
    }

    listen(event, callback){
        this.events.listen(event, callback);
    }

    setActionData(actionName, data) {
        const ndx = this.actions.findIndex(action => action.name == actionName);
        if (ndx > -1) {
            this.actions[ndx].data = data;
        }
    }

    setDescription(description) {
        this.prompt.querySelector('.prompt-description').innerHTML = description;
    }

    show() {
        document.body.style.overflow = 'hidden';
        document.body.appendChild(this.container);
        this.prompt.focus();
    }

    close(action) {
        this.container.remove();
        document.body.style.overflow = 'auto';
        this.events.emit('close', action || '');
    }
}