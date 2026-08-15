<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    seasons: {
        type: Array,
        default: () => [],
    },
    selectedSeason: {
        type: Object,
        default: null,
    },
    routeName: {
        type: String,
        required: true,
    },
    routeParams: {
        type: Object,
        default: () => ({}),
    },
    extraQuery: {
        type: Object,
        default: () => ({}),
    },
});

const selectedSlug = computed(() => props.selectedSeason?.slug || '');

const displayName = (season) => {
    if (!season?.name) return '';
    const match = String(season.name).match(/^(\d{4})-(\d{2})$/);
    return match ? `${match[1]}/${match[2]}` : season.name;
};

const onChange = (event) => {
    const slug = event.target.value;
    const season = props.seasons.find((item) => item.slug === slug);
    const query = {
        ...props.extraQuery,
        season: slug || undefined,
    };

    // Archived seasons have no upcoming gameweeks — show completed results by default
    if (season && !season.is_current) {
        if (!query.status || query.status === 'upcoming') {
            query.status = 'completed';
        }
    } else if (season?.is_current && query.status === 'completed') {
        delete query.status;
    }

    router.get(
        route(props.routeName, props.routeParams),
        query,
        {
            preserveState: false,
            preserveScroll: true,
        }
    );
};
</script>

<template>
    <div v-if="seasons?.length" class="flex items-center gap-2">
        <label for="season-switcher" class="text-sm text-gray-600 whitespace-nowrap">Season</label>
        <select
            id="season-switcher"
            class="rounded-lg border-gray-300 text-sm focus:border-green-500 focus:ring-green-500"
            :value="selectedSlug"
            @change="onChange"
        >
            <option
                v-for="season in seasons"
                :key="season.id"
                :value="season.slug"
            >
                {{ displayName(season) }}{{ season.is_current ? ' (current)' : '' }}
            </option>
        </select>
    </div>
</template>
