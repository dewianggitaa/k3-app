<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, MoreHorizontal } from 'lucide-vue-next';

const props = defineProps({
    links: Array,
    meta: Object,
});

// Fungsi untuk mengecek label bawaan Laravel
const isPrevious = (label) => label.includes('Previous') || label.includes('laquo');
const isNext = (label) => label.includes('Next') || label.includes('raquo');
const isEllipsis = (label) => label === '...';

const processedLinks = computed(() => {
    if (!props.links) return [];
    
    const activeIndex = props.links.findIndex(l => l.active);
    
    const numberIndices = [];
    props.links.forEach((link, idx) => {
        if (!isPrevious(link.label) && !isNext(link.label) && !isEllipsis(link.label)) {
            numberIndices.push(idx);
        }
    });

    let mobileVisibleIndices = new Set();
    
    if (numberIndices.length <= 4) {
        numberIndices.forEach(idx => mobileVisibleIndices.add(idx));
    } else {
        let activeNumIdx = numberIndices.indexOf(activeIndex);
        if (activeNumIdx === -1) activeNumIdx = 0;
        
        let start = activeNumIdx - 1;
        let end = activeNumIdx + 2;
        
        if (start < 0) {
            end += Math.abs(start);
            start = 0;
        }
        
        if (end > numberIndices.length - 1) {
            start -= (end - (numberIndices.length - 1));
            end = numberIndices.length - 1;
            if (start < 0) start = 0;
        }
        
        for (let i = start; i <= end; i++) {
            mobileVisibleIndices.add(numberIndices[i]);
        }
    }

    return props.links.map((link, index) => {
        const isPrevOrNext = isPrevious(link.label) || isNext(link.label);
        const isEllipsisText = isEllipsis(link.label);
        
        let mobileClass = 'flex'; 
        if (!isPrevOrNext) {
            if (isEllipsisText) {
                mobileClass = 'hidden sm:flex';
            } else if (!mobileVisibleIndices.has(index)) {
                mobileClass = 'hidden sm:flex';
            }
        }
        
        return {
            ...link,
            mobileClass
        };
    });
});
</script>

<template>
    <div v-if="links && links.length > 3" class="flex flex-col sm:flex-row items-center justify-between gap-4 w-full mt-6">
        
        <div v-if="meta" class="text-[12px] text-ink/60 dark:text-ink-dark/60 font-medium order-2 sm:order-1 text-center sm:text-left">
            Menampilkan <span class="font-bold text-ink dark:text-ink-dark">{{ meta.from || 0 }}</span> 
            - <span class="font-bold text-ink dark:text-ink-dark">{{ meta.to || 0 }}</span> 
            dari <span class="font-bold text-ink dark:text-ink-dark">{{ meta.total || 0 }}</span>
        </div>
        <div v-else class="order-2 sm:order-1"></div> 

        <div class="flex items-center justify-center gap-1 sm:gap-1.5 order-1 sm:order-2">
            <template v-for="(link, key) in processedLinks" :key="key">
                
                <div 
                    v-if="isEllipsis(link.label)" 
                    :class="[link.mobileClass, 'items-center justify-center w-7 h-7 sm:w-8 sm:h-8 text-ink/40 dark:text-ink-dark/40 cursor-default select-none']"
                >
                    <MoreHorizontal class="w-4 h-4" />
                </div>
                
                <div 
                    v-else-if="link.url === null && (isPrevious(link.label) || isNext(link.label))" 
                    :class="[link.mobileClass, 'items-center justify-center w-7 h-7 sm:w-8 sm:h-8 text-ink/30 dark:text-ink-dark/30 cursor-not-allowed select-none']"
                >
                    <ChevronLeft v-if="isPrevious(link.label)" class="w-5 h-5" />
                    <ChevronRight v-if="isNext(link.label)" class="w-5 h-5" />
                </div>
                
                <Link 
                    v-else-if="isPrevious(link.label) || isNext(link.label)"
                    :href="link.url"
                    :class="[link.mobileClass, 'items-center justify-center w-7 h-7 sm:w-8 sm:h-8 text-ink/80 dark:text-ink-dark/80 hover:text-primary dark:hover:text-primary-dark transition-colors duration-200']"
                >
                    <ChevronLeft v-if="isPrevious(link.label)" class="w-5 h-5" />
                    <ChevronRight v-if="isNext(link.label)" class="w-5 h-5" />
                </Link>

                <Link 
                    v-else 
                    :href="link.url"
                    :class="[
                        link.mobileClass,
                        'items-center justify-center w-7 h-7 sm:w-8 sm:h-8 text-[13px] sm:text-[14px] font-medium transition-all duration-200',
                        link.active 
                        ? 'rounded-full bg-indigo-100 border border-indigo-400 text-indigo-700 dark:bg-indigo-900/30 dark:border-indigo-500 dark:text-indigo-300 shadow-sm' 
                        : 'rounded-full text-ink/70 dark:text-ink-dark/70 hover:bg-ghost dark:hover:bg-ghost-dark border border-transparent'
                    ]"
                >
                    <span v-html="link.label"></span>
                </Link>

            </template>
        </div>
        
    </div>
</template>