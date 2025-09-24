<div class="flex items-center space-x-2">
    <div class="flex-1 bg-gray-200 rounded-full h-2 dark:bg-gray-700">
        <div class="bg-primary-600 h-2 rounded-full transition-all duration-300" 
             style="width: {{ $getState() }}%"></div>
    </div>
    <span class="text-sm font-medium text-gray-900 dark:text-white">
        {{ $getState() }}%
    </span>
</div>
