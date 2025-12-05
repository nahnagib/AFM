@if(session('success'))
    <div class="container mx-auto px-4 mt-4">
        <div class="bg-green-100 border-r-4 border-green-500 text-green-700 p-4 rounded fade-in" role="alert">
            <p class="font-medium">{{ session('success') }}</p>
        </div>
    </div>
@endif

@if(session('error'))
    <div class="container mx-auto px-4 mt-4">
        <div class="bg-red-100 border-r-4 border-red-500 text-red-700 p-4 rounded fade-in" role="alert">
            <p class="font-medium">{{ session('error') }}</p>
        </div>
    </div>
@endif

@if(session('info'))
    <div class="container mx-auto px-4 mt-4">
        <div class="bg-blue-100 border-r-4 border-blue-500 text-blue-700 p-4 rounded fade-in" role="alert">
            <p class="font-medium">{{ session('info') }}</p>
        </div>
    </div>
@endif

@if($errors->any())
    <div class="container mx-auto px-4 mt-4">
        <div class="bg-red-100 border-r-4 border-red-500 text-red-700 p-4 rounded fade-in" role="alert">
            <p class="font-bold mb-2">يرجى تصحيح الأخطاء التالية:</p>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
