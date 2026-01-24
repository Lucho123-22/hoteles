<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Button from 'primevue/button';

function smoothScroll(id: string) {
    const element = document.getElementById(id);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}
</script>

<template>
    <Button class="lg:!hidden" text severity="secondary" rounded v-styleclass="{
        selector: '@next',
        enterFromClass: 'hidden',
        enterActiveClass: 'animate-scalein',
        leaveToClass: 'hidden',
        leaveActiveClass: 'animate-fadeout',
        hideOnOutsideClick: true
    }">
        <i class="pi pi-bars !text-2xl"></i>
    </Button>

    <div
        class="items-center bg-surface-0 dark:bg-surface-900 grow justify-between hidden lg:flex absolute lg:static w-full left-0 top-full px-12 lg:px-0 z-20 rounded-border">
        <ul class="list-none p-0 m-0 flex lg:items-center select-none flex-col lg:flex-row cursor-pointer gap-8">
            <li>
                <a @click="smoothScroll('hero')"
                    class="px-0 py-4 text-surface-900 dark:text-surface-0 font-medium text-xl">
                    <span>Home</span>
                </a>
            </li>
            <li>
                <a @click="smoothScroll('features')"
                    class="px-0 py-4 text-surface-900 dark:text-surface-0 font-medium text-xl">
                    <span>Features</span>
                </a>
            </li>
            <li>
                <a @click="smoothScroll('highlights')"
                    class="px-0 py-4 text-surface-900 dark:text-surface-0 font-medium text-xl">
                    <span>Highlights</span>
                </a>
            </li>
            <li>
                <a @click="smoothScroll('pricing')"
                    class="px-0 py-4 text-surface-900 dark:text-surface-0 font-medium text-xl">
                    <span>Pricing</span>
                </a>
            </li>
        </ul>
        <div class="flex border-t lg:border-t-0 border-surface py-4 lg:py-0 mt-4 lg:mt-0 gap-2">
            <Link v-if="$page.props.auth.user" :href="route('dashboard')"
                class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]">
                Dashboard
            </Link>
            <Link v-else :href="route('login')"
                class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]">
                Inicia sesión
            </Link>
        </div>
    </div>
</template>