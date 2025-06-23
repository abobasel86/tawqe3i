<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight" dir="rtl">
            {{ __('قوالب المستندات') }}
        </h2>
    </x-slot>
    <div class="py-12" dir="rtl">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">قوالبك المحفوظة</h3>
                        <a href="{{ route('templates.create') }}" style="background-color: #156b68;" class="text-white font-bold py-2 px-4 rounded hover:bg-opacity-90">
                            + إنشاء قالب جديد
                        </a>
                    </div>
                    
                    <div class="mt-4">
                        <table class="w-full text-right">
                            <thead class="border-b">
                                <tr class="bg-gray-50">
                                    <th class="p-2">اسم القالب</th>
                                    <th class="p-2">تاريخ الإنشاء</th>
                                    <th class="p-2">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($templates as $template)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="p-2 font-semibold">{{ $template->name }}</td>
                                        <td class="p-2">{{ $template->created_at->format('Y-m-d') }}</td>
                                        <td class="p-2 space-x-2 space-x-reverse">
                                            {{-- زر لاستخدام القالب في إنشاء مستند جديد (سنبنيه لاحقًا) --}}
                                            <a href="#" class="text-green-600 hover:underline">استخدام</a>
                                            <a href="{{ route('templates.edit', $template) }}" class="text-blue-600 hover:underline">تعديل الحقول</a>
                                            <form action="{{ route('templates.destroy', $template) }}" method="POST" class="inline-block" onsubmit="return confirm('هل أنت متأكد؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">حذف</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="p-4 text-center text-gray-500">
                                            لا يوجد لديك أي قوالب حاليًا.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>