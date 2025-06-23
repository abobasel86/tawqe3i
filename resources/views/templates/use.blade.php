<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight" dir="rtl">
            استخدام القالب: {{ $template->name }}
        </h2>
    </x-slot>

    <div class="py-12" dir="rtl">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 text-gray-900">

                    <form action="{{ route('templates.create_document', $template) }}" method="POST">
                        @csrf
                        
                        <!-- اسم المستند الجديد -->
                        <div class="mb-6">
                            <label for="document_name" class="block font-medium text-lg text-gray-700 mb-2">اسم المستند الجديد</label>
                            <input type="text" name="document_name" id="document_name" class="w-full border-gray-300 rounded-md shadow-sm" value="{{ $template->name }}" required>
                        </div>
                        
                        <hr class="my-6">

                        <!-- إسناد المشاركين للأدوار -->
                        <div>
                            <h3 class="block font-medium text-lg text-gray-700 mb-4">إسناد الموقّعين للأدوار</h3>
                            <div class="space-y-4">
                                @foreach($requiredRoles as $roleId)
                                    <div class="border p-4 rounded-lg">
                                        <p class="font-semibold text-md mb-2">دور الموقّع رقم {{ $roleId }}</p>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label for="participants[{{$roleId}}][name]" class="text-sm text-gray-600">الاسم</label>
                                                <input type="text" name="participants[{{$roleId}}][name]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                            </div>
                                            <div>
                                                <label for="participants[{{$roleId}}][email]" class="text-sm text-gray-600">البريد الإلكتروني</label>
                                                <input type="email" name="participants[{{$roleId}}][email]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- زر الإنشاء -->
                        <div class="mt-8 text-left">
                            <button type="submit" style="background-color: #156b68;" class="text-white font-bold py-3 px-6 rounded-lg text-lg hover:bg-opacity-90">
                                إنشاء المستند من القالب
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
