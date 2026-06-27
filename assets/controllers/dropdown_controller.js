import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['menu']

    connect() {
        this.closeHandler = (e) => {
            if (!this.element.contains(e.target)) {
                this.close()
            }
        }
        document.addEventListener('click', this.closeHandler)
    }

    disconnect() {
        document.removeEventListener('click', this.closeHandler)
    }

    toggle() {
        this.menuTarget.classList.toggle('hidden')
    }

    close() {
        this.menuTarget.classList.add('hidden')
    }
}
