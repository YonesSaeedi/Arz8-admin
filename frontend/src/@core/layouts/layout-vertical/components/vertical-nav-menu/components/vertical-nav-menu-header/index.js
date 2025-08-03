import { useUtils as useI18nUtils } from '@core/libs/i18n'
import { useUtils as useAclUtils } from '@core/libs/acl'

const { t } = useI18nUtils()
const { canViewVerticalNavMenuHeader } = useAclUtils()

export default {
  props: {
    item: {
      type: Object,
      required: true,
    },
  },
  render(h) {
    const span = h('span', {}, t(this.item.header))
    const icon = h('feather-icon', { props: { icon: 'MoreHorizontalIcon', size: '18' } })
    if (canViewVerticalNavMenuHeader(this.item)) {
        if(this.item.header != "تنظیمات" || (this.activeUserInfo.role === 'admin' || this.accessUserLogin.setting.list || this.accessUserLogin['setting-crypto'].list
            || this.accessUserLogin['setting-markets'].list ||this.accessUserLogin['setting-networks'].list || this.accessUserLogin.admins.list))
            return h('li', { class: 'navigation-header text-truncate' }, [span, icon])
    }
    return h()
  },
}
