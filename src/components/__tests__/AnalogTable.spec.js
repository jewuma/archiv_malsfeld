import { describe, it, expect, vi } from 'vitest'
import { shallowMount } from '@vue/test-utils'
import AnalogTable from '../AnalogTable.vue'

describe('AnalogTable', () => {
  it('verwendet Analogdaten aus item ohne erneuten Fetch', async () => {
    const axiosGet = vi.fn().mockResolvedValue({ data: { data: [{ id: 7 }] } })

    const wrapper = shallowMount(AnalogTable, {
      props: {
        item: { id: 42, analogobjekte: [{ id: 7 }] },
        fixedData: {
          objekttypen: [],
          lagerorte: [],
          quellen: [],
          regale: [],
          faecher: [],
          plaetze: [],
        },
      },
      global: {
        mocks: {
          $axios: {
            get: axiosGet,
          },
        },
      },
    })

    await wrapper.vm.$nextTick()

    expect(axiosGet).not.toHaveBeenCalled()
    expect(wrapper.vm.analogInfo).toEqual([{ id: 7 }])
  })
})
