import { describe, expect, it, vi } from 'vitest'
import ArchivSuche from '../ArchivSuche.vue'

describe('ArchivSuche', () => {
  it('behaelt bearbeitete Werte nach dem Auf- und Zuklappen', async () => {
    const postMock = vi.fn().mockResolvedValue({ data: {} })
    const vm = {
      tableData: [
        {
          id: 1,
          titel: 'Alter Titel',
          expanded: false,
        },
      ],
      $axios: {
        post: postMock,
      },
    }

    await ArchivSuche.methods.entryEdited.call(vm, {
      id: 1,
      fieldName: 'titel',
      value: 'Neuer Titel',
    })

    ArchivSuche.methods.toggleExpand.call(vm, { id: 1 })

    expect(postMock).toHaveBeenCalledWith('/Archivobjekte/update', {
      id: 1,
      titel: 'Neuer Titel',
    })
    expect(vm.tableData[0].titel).toBe('Neuer Titel')
    expect(vm.tableData[0].expanded).toBe(true)
  })
})
