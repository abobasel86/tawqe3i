<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight" dir="rtl">
            إعداد المستند: {{ $document->name }}
        </h2>
    </x-slot>

    <!-- The main component that controls everything -->
    <div class="py-12" 
         x-data="documentEditor(
            '{{ route('documents.fields.update', $document) }}', 
            {{ json_encode($document->fields ?? []) }}, 
            {{ json_encode($document->participants) }}
         )">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-6" dir="rtl">
            
            <!-- Main Area: PDF Viewer with Interactive Fields Layer -->
            <div class="lg:col-span-2 bg-gray-200 relative shadow-sm sm:rounded-lg" x-ref="container">
                
                <!-- This is the interactive layer for fields -->
                <div class="absolute top-0 left-0 w-full h-full" 
                     :class="{ 'pointer-events-none': !dragging && !resizing }">
                    <template x-for="field in fields" :key="field.id">
                        <div
                            class="absolute border-2 border-dashed cursor-move flex items-center justify-center text-xs font-bold text-white rounded-sm pointer-events-auto"
                            :style="`left: ${field.x}px; top: ${field.y}px; width: ${field.width}px; height: ${field.height}px; background-color: ${getParticipantColor(field.participant_id, 0.7)}; border-color: ${getParticipantColor(field.participant_id, 1)};`"
                            @mousedown.prevent="startDrag($event, field)"
                        >
                            <span class="bg-black bg-opacity-50 p-1 rounded" x-text="getFieldLabel(field)"></span>
                            <!-- Resize Handle -->
                            <div class="absolute -bottom-1 -right-1 w-4 h-4 cursor-nwse-resize pointer-events-auto bg-white border-2 rounded-full" 
                                 :style="`border-color: ${getParticipantColor(field.participant_id, 1)}`" 
                                 @mousedown.prevent.stop="startResize($event, field)">
                            </div>
                        </div>
                    </template>
                </div>

                <!-- PDF Iframe Layer -->
                <iframe src="data:application/pdf;base64,{{ $base64Pdf }}" width="100%" height="750px" class="relative"></iframe>
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
                    <p class="text-sm text-gray-600 mb-3">حدد موقّعًا من الأسفل، ثم أضف الحقول المطلوبة.</p>
                    <div class="grid grid-cols-2 gap-4">
                        <button @click="addField('signature')" class="p-2 border rounded hover:bg-gray-100 disabled:opacity-50" :disabled="!selectedParticipantId">حقل توقيع</button>
                        <button @click="addField('text')" class="p-2 border rounded hover:bg-gray-100 disabled:opacity-50" :disabled="!selectedParticipantId">حقل نص</button>
                        <button @click="addField('date')" class="p-2 border rounded hover:bg-gray-100 disabled:opacity-50" :disabled="!selectedParticipantId">حقل تاريخ</button>
                    </div>
                </div>

                <!-- 2. Signers Panel -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium mb-4">2. الموقّعون</h3>
                    <form method="POST" action="{{ route('documents.participants.store', $document) }}" class="mb-4"> @csrf <div class="space-y-2"><input type="text" name="name" placeholder="اسم الموقّع" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required><input type="email" name="email" placeholder="البريد الإلكتروني" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required><button type="submit" style="background-color: #156b68;" class="w-full text-white font-bold py-2 px-4 rounded hover:bg-opacity-90">إضافة موقّع</button></div> </form>
                    <hr class="mb-4">
                    <div class="space-y-3">
                        @forelse ($document->participants as $participant)
                            <div class="flex items-center p-2 rounded-lg" :class="{ 'bg-blue-100 ring-2 ring-blue-400': selectedParticipantId == {{ $participant->id }} }"><label class="flex items-center w-full cursor-pointer"><input type="radio" name="selected_participant" value="{{ $participant->id }}" x-model.number="selectedParticipantId" class="h-4 w-4"><span class="mr-3" style="width: 15px; height: 15px; border-radius: 50%; background-color: {{ ['#3B82F6', '#F59E0B', '#10B981', '#EF4444', '#8B5CF6'][$loop->index % 5] }};"></span><span>{{ $participant->name }}</span></label></div>
                        @empty
                            <p class="text-center text-gray-500">الرجاء إضافة موقّعين أولاً.</p>
                        @endforelse
                    </div>
                </div>

                <!-- 3. Send Panel -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium mb-4">3. إرسال للتوقيع</h3>
                    <form action="{{ route('documents.send', $document) }}" method="POST" onsubmit="return confirm('هل أنت متأكد وجاهز لإرسال المستند؟');">@csrf<div><label for="flow_type" class="block font-medium text-sm text-gray-700">اختر مسار التوقيع:</label><select name="flow_type" id="flow_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"><option value="parallel">متوازي</option><option value="sequential">تسلسلي</option></select></div><button type="submit" class="w-full mt-4 bg-green-600 text-white font-bold py-2 px-4 rounded hover:bg-green-700">إرسال الآن</button></form>
                </div>
                <!-- 4. Assign to Folder Panel -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-medium mb-4">إسناد إلى مجلد</h3>
    <form action="{{ route('documents.assignFolder', $document) }}" method="POST">
        @csrf
        <label for="folder_id" class="sr-only">اختر مجلدًا</label>
        <select name="folder_id" id="folder_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
            <option value="">-- اختر مجلد --</option>
            @foreach(Auth::user()->folders as $folder)
                <option value="{{ $folder->id }}">{{ $folder->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="w-full mt-2 bg-gray-600 text-white py-2 rounded-md hover:bg-gray-700">إسناد</button>
    </form>
</div>
            </div>
        </div>
        
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('documentEditor', (saveUrl, initialFields, initialParticipants) => ({
                    fields: initialFields,
                    participants: initialParticipants,
                    selectedParticipantId: initialParticipants.length > 0 ? initialParticipants[0].id : null,
                    saving: false, dragging: false, resizing: false, activeField: null,
                    
                    init() { this.fields.forEach((field, i) => field.id = field.id || Date.now() + i); },
                    getParticipantColor(id, opacity=1) {
                        if(!id) return `rgba(107, 114, 128, ${opacity})`;
                        const colors = ['#3B82F6','#F59E0B','#10B981','#EF4444','#8B5CF6'];
                        const pIndex = this.participants.findIndex(p => p.id === id);
                        if (pIndex === -1) return `rgba(107, 114, 128, ${opacity})`;
                        const hex = colors[pIndex % colors.length];
                        let r=0,g=0,b=0; if(hex.length === 7){r=parseInt(hex.slice(1,3),16);g=parseInt(hex.slice(3,5),16);b=parseInt(hex.slice(5,7),16);}
                        return `rgba(${r},${g},${b},${opacity})`;
                    },
                    getFieldLabel(field) {
                        const p = this.participants.find(p => p.id === field.participant_id);
                        const typeLabels = {'signature':'توقيع','text':'نص','date':'تاريخ'};
                        return `${typeLabels[field.type] || field.type} - ${p ? p.name : 'غير محدد'}`;
                    },
                    addField(type) {
                        if (this.selectedParticipantId === null) { alert('الرجاء تحديد موقّع من القائمة أولاً.'); return; }
                        this.fields.push({ id: Date.now(), type: type, participant_id: this.selectedParticipantId, page: 1, x: 30, y: 30, width: 150, height: 50 });
                    },
                    startDrag(event, field) {
                        if(this.resizing) return; 
                        this.dragging = true;
                        const containerRect = this.$refs.container.getBoundingClientRect();
                        let offsetX = event.clientX - containerRect.left - field.x;
                        let offsetY = event.clientY - containerRect.top - field.y;
                        const moveHandler = (e) => { 
                            if (!this.dragging) return; 
                            field.x = e.clientX - containerRect.left - offsetX; 
                            field.y = e.clientY - containerRect.top - offsetY; 
                        };
                        const upHandler = () => { this.dragging = false; document.removeEventListener('mousemove', moveHandler); document.removeEventListener('mouseup', upHandler); };
                        
                        document.addEventListener('mousemove', moveHandler);
                        document.addEventListener('mouseup', upHandler);
                    },
                    startResize(event, field) {
                        this.resizing = true;
                        let initialWidth = field.width; 
                        let initialHeight = field.height;
                        let initialMouseX = event.clientX; 
                        let initialMouseY = event.clientY;
                        const moveHandler = (e) => { 
                            if (!this.resizing) return; 
                            field.width = Math.max(50, initialWidth + (e.clientX - initialMouseX)); 
                            field.height = Math.max(40, initialHeight + (e.clientY - initialMouseY)); 
                        };
                        const upHandler = () => { this.resizing = false; document.removeEventListener('mousemove', moveHandler); document.removeEventListener('mouseup', upHandler); };
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
    </x-app-layout>