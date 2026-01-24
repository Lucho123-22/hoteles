<template>
    <div v-if="layout === 'list'" class="flex flex-col">
        <div v-for="i in 6" :key="i">
            <div class="flex flex-col xl:flex-row xl:items-start p-6 gap-6" :class="{ 'border-t border-surface-200 dark:border-surface-700': i !== 0 }">
                <Skeleton class="w-9/12 sm:w-64 xl:w-40 h-24 mx-auto" />
                <div class="flex flex-col sm:flex-row justify-between items-center xl:items-start flex-1 gap-6">
                    <div class="flex flex-col items-center sm:items-start gap-4">
                        <Skeleton width="8rem" height="2rem" />
                        <Skeleton width="6rem" height="1rem" />
                        <div class="flex items-center gap-4">
                            <Skeleton width="6rem" height="1rem" />
                            <Skeleton width="3rem" height="1rem" />
                        </div>
                    </div>
                    <div class="flex sm:flex-col items-center sm:items-end gap-4 sm:gap-2">
                        <Skeleton width="4rem" height="2rem" />
                        <Skeleton size="3rem" shape="circle" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div v-else class="grid grid-cols-12 gap-4">
        <div v-for="i in 6" :key="i" class="col-span-12 sm:col-span-6 xl:col-span-4 p-2">
            <div class="p-6 border border-surface-200 dark:border-surface-700 bg-surface-0 dark:bg-surface-900 rounded">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <Skeleton width="6rem" height="2rem" />
                    <Skeleton width="3rem" height="1rem" />
                </div>
                <div class="flex flex-col items-center gap-4 py-8">
                    <Skeleton width="75%" height="10rem" />
                    <Skeleton width="8rem" height="2rem" />
                    <Skeleton width="6rem" height="1rem" />
                </div>
                <div class="flex items-center justify-between">
                    <Skeleton width="4rem" height="2rem" />
                    <Skeleton width="6rem" height="1rem" shape="circle" size="3rem" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Skeleton from 'primevue/skeleton';
import type { LayoutType } from '../interface/Roommanagement';

defineProps<{
    layout: LayoutType;
}>();
</script>