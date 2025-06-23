<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>توقيع المستند: {{ $document->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    {{-- مكتبة لوحة التوقيع --}}
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
</head>
<body class="bg-gray-100">

    <div class="container mx-auto p-4" x-data="signaturePage()">
        
        <!-- Header -->
        <div class="bg-white p-4 rounded-lg shadow mb-4">
            <h1 class="text-2xl font-bold">طلب توقيع للمستند: {{ $document->name }}</h1>
            <p class="text-gray-600">مطلوب من: {{ $participant->name }} ({{ $participant->email }})</p>
        </div>

        <!-- PDF Viewer and Signature Area -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- PDF Viewer -->
            <div class="md:col-span-2 bg-white rounded-lg shadow p-2">
                <iframe src="data:application/pdf;base64,{{ $base64Pdf }}" width="100%" height="800px"></iframe>
            </div>

            <!-- Signature Panel -->
            <div class="md:col-span-1 bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">التوقيع المطلوب</h2>
                
                <template x-if="signatureFieldExists">
                    <div>
                        <label class="font-semibold">الرجاء رسم توقيعك في المربع أدناه:</label>
                        <div class="bg-gray-200 rounded-lg mt-2 relative">
                            <canvas x-ref="pad" class="w-full h-48"></canvas>
                        </div>
                        <div class="flex justify-between mt-2">
                            <button @click="clearPad" class="text-sm text-gray-600 hover:text-red-600">مسح</button>
                        </div>
                    </div>
                </template>
                <template x-if="!signatureFieldExists">
                    <p class="text-red-600">لم يتم تحديد حقل توقيع لهذا المستخدم في المستند.</p>
                </template>


                <!-- Submission Form -->
                <form action="{{ route('sign.store', $participant->token) }}" method="POST" @submit.prevent="submitForm">
                    @csrf
                    <!-- Hidden input to store signature data -->
                    <input type="hidden" name="signature" x-model="signatureData">
                    
                    <button type="submit" class="w-full mt-8 bg-green-600 text-white font-bold py-3 px-4 rounded hover:bg-green-700 text-lg" :disabled="!signatureFieldExists">
                        توقيع واعتماد المستند
                    </button>
                </form>

            </div>
        </div>
    </div>

    <script>
        function signaturePage() {
            return {
                fields: @json($document->fields ?? []),
                participantId: {{ $participant->id }},
                signaturePad: null,
                signatureData: '',
                signatureFieldExists: false,

                init() {
                    const signatureField = this.fields.find(f => f.participant_id === this.participantId && f.type === 'signature');
                    this.signatureFieldExists = !!signatureField;

                    if (!this.signatureFieldExists) {
                        console.warn("No signature field found for this participant.");
                        return;
                    }

                    this.$nextTick(() => {
                        const canvas = this.$refs.pad;
                        if (!canvas) return;
                        this.signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgb(229, 231, 235)' });
                        const ratio = Math.max(window.devicePixelRatio || 1, 1);
                        canvas.width = canvas.offsetWidth * ratio;
                        canvas.height = canvas.offsetHeight * ratio;
                        canvas.getContext("2d").scale(ratio, ratio);
                        this.signaturePad.clear();
                    });
                },
                clearPad() {
                    if (this.signaturePad) this.signaturePad.clear();
                },
                submitForm(event) {
                    if (this.signaturePad && !this.signaturePad.isEmpty()) {
                        this.signatureData = this.signaturePad.toDataURL();
                        event.target.submit();
                    } else {
                        alert('الرجاء التوقيع أولاً قبل الإرسال.');
                    }
                }
            }
        }
    </script>
</body>
</html>