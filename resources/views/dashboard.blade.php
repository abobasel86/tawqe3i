<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight" style="text-align: right;">
            {{ __('لوحة التحكم') }}
        </h2>
    </x-slot>

    <div class="py-12" dir="rtl">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

                <!-- Sidebar for Folders -->
                <div class="md:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-medium mb-4">المجلدات</h3>
                        
                        <!-- New Folder Form -->
                        <form action="{{ route('folders.store') }}" method="POST" class="mb-4">
                            @csrf
                            <input type="text" name="name" placeholder="اسم المجلد الجديد" class="w-full border-gray-300 rounded-md shadow-sm mb-2" required>
                            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">إنشاء مجلد</button>
                        </form>

                        <!-- Folders List -->
                        <ul class="space-y-2">
                            <li><a href="{{ route('dashboard') }}" class="block p-2 rounded-md {{ request()->routeIs('dashboard') && !request()->has('folder') ? 'bg-gray-200' : 'hover:bg-gray-100' }}">كل المستندات</a></li>
                            @foreach (Auth::user()->folders as $folder)
                                <li><a href="{{ route('dashboard', ['folder' => $folder->id]) }}" class="block p-2 rounded-md {{ request('folder') == $folder->id ? 'bg-gray-200' : 'hover:bg-gray-100' }}">{{ $folder->name }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <!-- Main Content for Documents -->
                <div class="md:col-span-3">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-medium">مستنداتك</h3>
                                <a href="{{ route('documents.create') }}" style="background-color: #156b68;" class="text-white font-bold py-2 px-4 rounded hover:bg-opacity-90">
                                    + رفع مستند جديد
                                </a>
                            </div>
                            
                            <hr class="my-4">

                            <!-- Documents Table... (same as before) -->
                             <div class="mt-4">
                                <table class="w-full text-right">
                                    <thead class="border-b"><tr class="bg-gray-50"><th class="p-2">اسم المستند</th><th class="p-2">الحالة</th><th class="p-2">تاريخ الرفع</th><th class="p-2">الإجراءات</th></tr></thead>
                                    <tbody>
                                        @forelse ($documents as $document)
                                            <tr class="border-b hover:bg-gray-50">
                                                <td class="p-2 font-semibold">{{ $document->name }}</td>
                                                <td class="p-2">
                                                    @if ($document->status === 'draft') <span class="px-2 py-1 text-xs font-bold leading-none text-gray-800 bg-gray-200 rounded-full">مسودة</span>
                                                    @elseif ($document->status === 'sent') <span class="px-2 py-1 text-xs font-bold leading-none text-yellow-800 bg-yellow-200 rounded-full">مرسل</span>
                                                    @elseif ($document->status === 'completed') <span class="px-2 py-1 text-xs font-bold leading-none text-green-800 bg-green-200 rounded-full">مكتمل</span> @endif
                                                </td>
                                                <td class="p-2">{{ $document->created_at->format('Y-m-d') }}</td>
                                                <td class="p-2 space-x-2 space-x-reverse">
                                                    @if ($document->status === 'draft' || $document->status === 'sent') <a href="{{ route('documents.show', $document) }}" class="text-blue-600 hover:underline">إعداد</a> @endif
                                                    @if ($document->status === 'completed') <a href="{{ route('documents.download', $document) }}" class="text-green-600 hover:underline">تنزيل النسخة الموقعة</a> @endif
                                                    <form action="{{ route('documents.destroy', $document) }}" method="POST" class="inline-block" onsubmit="return confirm('هل أنت متأكد؟');"> @csrf @method('DELETE') <button type="submit" class="text-red-600 hover:underline">حذف</button> </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="p-4 text-center text-gray-500">لا يوجد لديك أي مستندات في هذا المجلد.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>