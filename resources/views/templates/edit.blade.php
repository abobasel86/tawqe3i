<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight" dir="rtl">
            تعديل حقول القالب: {{ $template->name }}
        </h2>
    </x-slot>

    <!-- The main component that controls everything -->
    <div class="py-12"
         x-data="templateEditor(
            '{{ route('templates.update', $template) }}',
            '{{ $base64Pdf }}',
            {{ json_encode($template->fields ?? []) }}
         )">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-6" dir="rtl">
            
            <!-- Main Area: PDF Viewer with Interactive Fields Layer -->
            <div class="lg:col-span-2 bg-gray-200 relative shadow-sm sm:rounded-lg overflow-y-auto" x-ref="container" @scroll="onScroll">
                <div class="space-y-4 p-4" x-ref="pages"></div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                 <!-- Save Button -->
                 <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <button @click="saveFields" :disabled="saving" :class="saving ? 'bg-yellow-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'" class="w-full text-white font-bold py-3 px-4 rounded transition text-lg">
                        <span x-show="!saving">✓ حفظ أماكن الحقول</span>
                        <span x-show="saving">جاري الحفظ...</span>
                    </button>
                </div>

                <!-- 1. Toolbox -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium mb-4">1. صندوق الأدوات</h3>
                    <p class="text-sm text-gray-600 mb-3">حدد "دور" الموقّع من الأسفل، ثم أضف الحقول.</p>
                    <div class="grid grid-cols-2 gap-4">
                        <button @click="addField('signature')" class="p-2 border rounded hover:bg-gray-100 disabled:opacity-50" :disabled="!selectedRoleId">حقل توقيع</button>
                        <button @click="addField('text')" class="p-2 border rounded hover:bg-gray-100 disabled:opacity-50" :disabled="!selectedRoleId">حقل نص</button>
                        <button @click="addField('date')" class="p-2 border rounded hover:bg-gray-100 disabled:opacity-50" :disabled="!selectedRoleId">حقل تاريخ</button>
                    </div>
                </div>

                <!-- 2. Signer Roles Panel -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium mb-4">2. أدوار الموقّعين</h3>
                    <p class="text-sm text-gray-600 mb-3">هذه مجرد أدوار وهمية (الموقّع الأول، الثاني...). عند استخدام القالب، سيتم إسناد أشخاص حقيقيين لهذه الأدوار.</p>
                    <div class="space-y-3">
                        <template x-for="role in signerRoles" :key="role.id">
                            <div class="flex items-center p-2 rounded-lg" :class="{ 'bg-blue-100 ring-2 ring-blue-400': selectedRoleId == role.id }">
                                <label class="flex items-center w-full cursor-pointer">
                                    <input type="radio" name="selected_role" :value="role.id" x-model.number="selectedRoleId" class="h-4 w-4">
                                    <span class="mr-3" style="width: 15px; height: 15px; border-radius: 50%;" :style="`background-color: ${getRoleColor(role.id, 1)}`"></span>
                                    <span x-text="role.name"></span>
                                </label>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
        
        <script src="{{ asset('libs/pdf.min.js') }}"></script>
        <script src="{{ asset('libs/pdf.worker.min.js') }}"></script>
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('templateEditor', (saveUrl, pdfData, initialFields) => ({
                    fields: initialFields || [],
                    signerRoles: [
                        { id: 1, name: 'الموقّع الأول' },
                        { id: 2, name: 'الموقّع الثاني' },
                        { id: 3, name: 'الموقّع الثالث' },
                    ],
                    selectedRoleId: 1,
                    saving: false, dragging: false, resizing: false, activeField: null,
                    pdfData: pdfData,
                    currentPage: 1,
                    numPages: 0,
                    
                    init() {
                        this.fields.forEach((field, i) => field.id = field.id || Date.now() + i);
                        this.renderPdf();
                        this.$watch('fields', () => this.updateFields(), {deep: true});
                    },
                    getRoleColor(id, opacity=1) {
                        const colors = ['#3B82F6','#F59E0B','#10B981','#EF4444','#8B5CF6'];
                        const hex = colors[((id || 1) - 1) % colors.length];
                        let r=0,g=0,b=0; if(hex.length === 7){r=parseInt(hex.slice(1,3),16);g=parseInt(hex.slice(3,5),16);b=parseInt(hex.slice(5,7),16);}
                        return `rgba(${r},${g},${b},${opacity})`;
                    },
                    getFieldLabel(field) {
                        const role = this.signerRoles.find(r => r.id === field.role_id);
                        const typeLabels = {'signature':'توقيع','text':'نص','date':'تاريخ'};
                        return `${typeLabels[field.type] || field.type} - ${role ? role.name : 'غير محدد'}`;
                    },
                    renderPdf() {
                        const container = this.$refs.pages;
                        const raw = atob(this.pdfData);
                        const bytes = new Uint8Array(raw.length);
                        for (let i = 0; i < raw.length; i++) bytes[i] = raw.charCodeAt(i);
                        pdfjsLib.GlobalWorkerOptions.workerSrc = '/libs/pdf.worker.min.js';
                        pdfjsLib.getDocument({data: bytes}).promise.then(pdf => {
                            this.numPages = pdf.numPages;
                            const promises = [];
                            for (let n = 1; n <= pdf.numPages; n++) {
                                promises.push(pdf.getPage(n).then(page => {
                                    const viewport = page.getViewport({scale:1});
                                    const scale = container.clientWidth / viewport.width;
                                    const vp = page.getViewport({scale});
                                    const canvas = document.createElement('canvas');
                                    canvas.width = vp.width;
                                    canvas.height = vp.height;
                                    const ctx = canvas.getContext('2d');
                                    const div = document.createElement('div');
                                    div.style.position = 'relative';
                                    div.style.marginBottom = '20px';
                                    div.dataset.page = n;
                                    div.appendChild(canvas);
                                    const overlay = document.createElement('div');
                                    overlay.className = 'absolute top-0 left-0 w-full h-full';
                                    overlay.dataset.overlay = n;
                                    div.appendChild(overlay);
                                    container.appendChild(div);
                                    return page.render({canvasContext: ctx, viewport: vp}).promise;
                                }));
                            }
                            return Promise.all(promises);
                        }).then(() => this.updateFields());
                    },
                    onScroll() {
                        const container = this.$refs.container;
                        const center = container.getBoundingClientRect().top + container.clientHeight / 2;
                        const pages = Array.from(container.querySelectorAll('[data-page]'));
                        for (const p of pages) {
                            const rect = p.getBoundingClientRect();
                            if (rect.top <= center && rect.bottom >= center) {
                                this.currentPage = parseInt(p.dataset.page);
                                break;
                            }
                        }
                    },
                    updateFields() {
                        const container = this.$refs.container;
                        const overlays = container.querySelectorAll('[data-overlay]');
                        overlays.forEach(o => o.innerHTML = '');
                        this.fields.forEach(field => {
                            const overlay = container.querySelector(`[data-overlay='${field.page}']`);
                            if(!overlay) return;
                            const el = document.createElement('div');
                            el.className = 'absolute border-2 border-dashed cursor-move flex items-center justify-center text-xs font-bold text-white rounded-sm pointer-events-auto';
                            el.style.left = field.x + 'px';
                            el.style.top = field.y + 'px';
                            el.style.width = field.width + 'px';
                            el.style.height = field.height + 'px';
                            el.style.backgroundColor = this.getRoleColor(field.role_id,0.7);
                            el.style.borderColor = this.getRoleColor(field.role_id,1);
                            el.addEventListener('mousedown', (e)=>{e.preventDefault(); this.startDrag(e, field);});
                            const label = document.createElement('span');
                            label.className = 'bg-black bg-opacity-50 p-1 rounded';
                            label.textContent = this.getFieldLabel(field);
                            el.appendChild(label);
                            const resize = document.createElement('div');
                            resize.className = 'absolute -bottom-1 -right-1 w-4 h-4 cursor-nwse-resize pointer-events-auto bg-white border-2 rounded-full';
                            resize.style.borderColor = this.getRoleColor(field.role_id,1);
                            resize.addEventListener('mousedown', (e)=>{e.preventDefault(); e.stopPropagation(); this.startResize(e, field);});
                            el.appendChild(resize);
                            overlay.appendChild(el);
                        });
                    },
                    addField(type) {
                        if (!this.selectedRoleId) { alert('الرجاء تحديد دور الموقّع أولاً.'); return; }
                        this.fields.push({ id: Date.now(), type: type, role_id: this.selectedRoleId, page: this.currentPage, x: 30, y: 30, width: 150, height: 50 });
                    },
                    startDrag(event, field) {
                        if(this.resizing) return; this.dragging = true; this.activeField = field;
                        const pageEl = event.target.closest('[data-page]');
                        const rect = pageEl.getBoundingClientRect();
                        let offsetX = event.clientX - rect.left - field.x;
                        let offsetY = event.clientY - rect.top - field.y;
                        const moveHandler = (e) => { if (!this.dragging) return; field.x = e.clientX - rect.left - offsetX; field.y = e.clientY - rect.top - offsetY; };
                        const upHandler = () => { this.dragging = false; this.activeField = null; document.removeEventListener('mousemove', moveHandler); document.removeEventListener('mouseup', upHandler); };
                        document.addEventListener('mousemove', moveHandler);
                        document.addEventListener('mouseup', upHandler);
                    },
                    startResize(event, field) {
                        this.resizing = true; this.activeField = field;
                        const pageEl = event.target.closest('[data-page]');
                        const rect = pageEl.getBoundingClientRect();
                        let initialWidth = field.width; let initialHeight = field.height;
                        let initialMouseX = event.clientX; let initialMouseY = event.clientY;
                        const moveHandler = (e) => { if (!this.resizing) return; field.width = Math.max(50, initialWidth + (e.clientX - initialMouseX)); field.height = Math.max(40, initialHeight + (e.clientY - initialMouseY)); };
                        const upHandler = () => { this.resizing = false; this.activeField = null; document.removeEventListener('mousemove', moveHandler); document.removeEventListener('mouseup', upHandler); };
                        document.addEventListener('mousemove', moveHandler);
                        document.addEventListener('mouseup', upHandler);
                    },
                    saveFields() {
                        this.saving = true;
                        fetch(saveUrl, {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ fields: this.fields })
                        })
                        .then(res => res.json())
                        .then(data => { setTimeout(() => { this.saving = false; alert(data.message); }, 500); })
                        .catch(() => { this.saving = false; alert('حدث خطأ أثناء الحفظ.'); });
                    }
                }));
            });
        </script>
    </div>
</x-app-layout>
