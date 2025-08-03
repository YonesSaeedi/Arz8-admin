<template>
    <li
        v-if="canViewVerticalNavMenuLink(item)"
        class="nav-item"
        :class="{
      'active': isActive,
      'disabled': item.disabled
    }"
    >
        <b-link
            v-bind="linkProps"
            class="d-flex align-items-center"
            v-if="checkAccess(linkProps.to.name)"
        >
            <feather-icon :icon="item.icon || 'CircleIcon'"/>
            <span class="menu-title text-truncate">{{ t(item.title) }}</span>
            <b-badge
                v-if="item.tag"
                pill
                :variant="item.tagVariant || 'primary'"
                class="mr-1 ml-auto"
            >
                {{ item.tag }}
            </b-badge>
        </b-link>
    </li>
</template>

<script>
import {useUtils as useAclUtils} from '@core/libs/acl'
import {BLink, BBadge} from 'bootstrap-vue'
import {useUtils as useI18nUtils} from '@core/libs/i18n'
import useVerticalNavMenuLink from './useVerticalNavMenuLink'
import mixinVerticalNavMenuLink from './mixinVerticalNavMenuLink'

export default {
    components: {
        BLink,
        BBadge,
    },
    mixins: [mixinVerticalNavMenuLink],
    props: {
        item: {
            type: Object,
            required: true,
        },
    },
    setup(props) {
        const {isActive, linkProps, updateIsActive} = useVerticalNavMenuLink(props.item)
        const {t} = useI18nUtils()
        const {canViewVerticalNavMenuLink} = useAclUtils()

        return {
            isActive,
            linkProps,
            updateIsActive,

            // ACL
            canViewVerticalNavMenuLink,

            // i18n
            t,
        }
    },
    methods: {
        checkAccess(nameRoute){
            if(nameRoute === 'dashboard')
                return true;
            if(Object.keys(this.access).includes(nameRoute)){
                if(this.accessUserLogin[this.access[nameRoute]].list === true || this.activeUserInfo.role === 'admin')
                    return true;
                else
                    return false
            }else
                return false;
        }
    },
    created() {
        //console.log(this.accessUserLogin);
    }

}
</script>
