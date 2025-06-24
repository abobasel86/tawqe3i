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
            {{ json_encode($template->fields ?? []) }},
            {{ $numPages }}
         )">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-6" dir="rtl">
            
            <!-- Main Area: PDF Viewer with Interactive Fields Layer -->
            <div class="lg:col-span-2 bg-gray-200 relative shadow-sm sm:rounded-lg" x-ref="container">
                <!-- Page Navigation -->
                <div class="bg-white p-2 flex flex-wrap items-center justify-center gap-2 mb-2">
                    <template x-for="page in numPages" :key="'nav'+page">
                        <button @click="changePage(page)" class="px-2 py-1 rounded" :class="currentPage === page ? 'bg-blue-600 text-white' : 'bg-gray-200'">
                            <span x-text="page"></span>
                        </button>
                    </template>
                    <span class="mx-2" x-text="currentPage + ' / ' + numPages"></span>
                </div>
                <!-- Fields Layer -->
                <div class="absolute top-0 left-0 w-full h-full" :class="{ 'pointer-events-none': dragging || resizing }">
                    <template x-for="field in fields.filter(f => f.page === currentPage)" :key="field.id">
                        <div
                            class="absolute border-2 border-dashed cursor-move flex items-center justify-center text-xs font-bold text-white rounded-sm pointer-events-auto"
                            :style="`left: ${field.x}px; top: ${field.y}px; width: ${field.width}px; height: ${field.height}px; background-color: ${getRoleColor(field.role_id, 0.7)}; border-color: ${getRoleColor(field.role_id, 1)};`"
                            @mousedown.prevent="startDrag($event, field)"
                        >
                            <span class="bg-black bg-opacity-50 p-1 rounded" x-text="getFieldLabel(field)"></span>
                            <div class="absolute -bottom-1 -right-1 w-4 h-4 cursor-nwse-resize pointer-events-auto bg-white border-2 rounded-full" :style="`border-color: ${getRoleColor(field.role_id, 1)}`" @mousedown.prevent.stop="startResize($event, field)"></div>
                        </div>
                    </template>
                </div>
                <!-- PDF Iframe Layer -->
                <iframe x-ref="pdfIframe" :src="pdfSrc + '#page=' + currentPage" width="100%" height="750px" class="relative"></iframe>
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
        
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('templateEditor', (saveUrl, initialFields, totalPages) => ({
                    fields: initialFields || [],
                    numPages: totalPages,
                    currentPage: 1,
                    pdfSrc: 'data:application/pdf;base64,{{ $base64Pdf }}',
                    signerRoles: [
                        { id: 1, name: 'الموقّع الأول' },
                        { id: 2, name: 'الموقّع الثاني' },
                        { id: 3, name: 'الموقّع الثالث' },
                    ],
                    selectedRoleId: 1,
                    saving: false, dragging: false, resizing: false, activeField: null,
                    
                    init() { this.fields.forEach((field, i) => field.id = field.id || Date.now() + i); },
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
                    addField(type) {
                        if (!this.selectedRoleId) { alert('الرجاء تحديد دور الموقّع أولاً.'); return; }
                        this.fields.push({ id: Date.now(), type: type, role_id: this.selectedRoleId, page: this.currentPage, x: 30, y: 30, width: 150, height: 50 });
                    },
                    startDrag(event, field) {
                        if(this.resizing) return; this.dragging = true; this.activeField = field;
                        const containerRect = this.$refs.container.getBoundingClientRect();
                        let offsetX = event.clientX - containerRect.left - field.x;
                        let offsetY = event.clientY - containerRect.top - field.y;
                        const moveHandler = (e) => { if (!this.dragging) return; field.x = e.clientX - containerRect.left - offsetX; field.y = e.clientY - containerRect.top - offsetY; };
                        const upHandler = () => { this.dragging = false; this.activeField = null; document.removeEventListener('mousemove', moveHandler); document.removeEventListener('mouseup', upHandler); };
                        document.addEventListener('mousemove', moveHandler);
                        document.addEventListener('mouseup', upHandler);
                    },
                    startResize(event, field) {
                        this.resizing = true; this.activeField = field;
                        let initialWidth = field.width; let initialHeight = field.height;
                        let initialMouseX = event.clientX; let initialMouseY = event.clientY;
                        const moveHandler = (e) => { if (!this.resizing) return; field.width = Math.max(50, initialWidth + (e.clientX - initialMouseX)); field.height = Math.max(40, initialHeight + (e.clientY - initialMouseY)); };
                        const upHandler = () => { this.resizing = false; this.activeField = null; document.removeEventListener('mousemove', moveHandler); document.removeEventListener('mouseup', upHandler); };
                        document.addEventListener('mousemove', moveHandler);
                        document.addEventListener('mouseup', upHandler);
                    },
                    changePage(page) {
                        this.currentPage = page;
                        this.$refs.pdfIframe.src = this.pdfSrc + '#page=' + page;
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
