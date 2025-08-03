<template>
    <b-card no-body>
        <b-card-body class="d-flex justify-content-between align-items-center">
            <div class="truncate">
                <h3 class="mb-25 font-weight-bolder" :class="classTitle">
                    {{ !formatNumber ? statistic : statistic.toLocaleString() }}
                </h3>
                <span>{{ statisticTitle }}</span>
            </div>
            <b-avatar
                :variant="`light-${color}`"
                size="45"
            >
                <feather-icon
                    size="21"
                    :icon="icon"
                />
            </b-avatar>
        </b-card-body>
    </b-card>
</template>

<script>
    import {BCard, BCardBody, BAvatar} from 'bootstrap-vue'

    export default {
        components: {
            BCard,
            BCardBody,
            BAvatar,
        },
        props: {
            icon: {
                type: String,
                required: true,
            },
            statistic: {
                type: [Number, String],
                required: true,
            },
            statisticTitle: {
                type: String,
                default: '',
            },
            color: {
                type: String,
                default: 'primary',
            },
            classTitle: {
                type: String,
                default: '',
            },
            formatNumber: {
                type: Boolean,
                default: false,
            },
        },
    }
</script>
