<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight" dir="rtl">
            إنشاء قالب جديد
        </h2>
    </x-slot>
    <div class="py-12" dir="rtl">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('templates.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div>
                            <label for="name">اسم القالب</label>
                            <input id="name" class="block mt-1 w-full" type="text" name="name" required autofocus />
                        </div>
                        <div class="mt-4">
                            <label for="document">ملف الـ PDF الأساسي</label>
                            <input id="document" class="block mt-1 w-full" type="file" name="document" required />
                        </div>
                        <div class="flex items-center justify-end mt-4">
                            <button type="submit" style="background-color: #156b68;" class="text-white font-bold py-2 px-4 rounded">
                                إنشاء وحفظ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>