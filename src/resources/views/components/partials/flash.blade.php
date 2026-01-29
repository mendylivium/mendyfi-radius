<div x-data="toastNotifications()" x-init="init()">
    <!-- Toast Container -->
    <div class="toast-container" id="toast-container">
        <template x-for="toast in toasts" :key="toast.id">
            <div 
                class="toast-notification" 
                :class="'toast-' + toast.type + (toast.hiding ? ' toast-hiding' : '')"
                x-show="toast.visible"
                x-transition:enter="transition ease-out duration-300"
                x-transition:leave="transition ease-in duration-200"
            >
                <span class="toast-icon">
                    <i :class="getIcon(toast.type)"></i>
                </span>
                <span class="toast-message" x-text="toast.message"></span>
                <button class="toast-close" @click="removeToast(toast.id)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </template>
    </div>
</div>

<script>
function toastNotifications() {
    return {
        toasts: [],
        
        init() {
            // Check for session flash messages
            @if (session('type') && session('message'))
                this.addToast('{{ session('type') }}', '{{ session('message') }}');
            @endif
            
            // Listen for Livewire events
            window.addEventListener('toast', (event) => {
                this.addToast(event.detail.type || 'info', event.detail.message);
            });
            
            // Also listen for older style flash events
            if (typeof Livewire !== 'undefined') {
                Livewire.on('flash', (data) => {
                    this.addToast(data.type || 'info', data.message);
                });
            }
        },
        
        addToast(type, message) {
            const id = Date.now();
            this.toasts.push({
                id: id,
                type: type,
                message: message,
                visible: true,
                hiding: false
            });
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                this.removeToast(id);
            }, 3000);
        },
        
        removeToast(id) {
            const toast = this.toasts.find(t => t.id === id);
            if (toast) {
                toast.hiding = true;
                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }, 300);
            }
        },
        
        getIcon(type) {
            const icons = {
                'success': 'fas fa-check-circle',
                'danger': 'fas fa-exclamation-circle',
                'error': 'fas fa-exclamation-circle',
                'warning': 'fas fa-exclamation-triangle',
                'info': 'fas fa-info-circle'
            };
            return icons[type] || icons['info'];
        }
    }
}
</script>
