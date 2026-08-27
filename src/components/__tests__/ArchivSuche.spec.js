import { describe, expect, it, vi } from 'vitest'
import ArchivSuche from '../ArchivSuche.vue'

describe('ArchivSuche', () => {
  it('bereitet das Hinzufügen eines Digitalobjekts mit einer Posteingangsdatei vor', async () => {
    const postMock = vi
      .fn()
      .mockResolvedValue({ data: { data: [{ path: 'archiveingang/neu.pdf' }] } })
    const vm = {
      $axios: { post: postMock },
      addDigitalDialogParent: null,
      addDigitalDialogRow: null,
      addDigitalDialogTree: [],
      showAddDigitalDialog: false,
    }

    await ArchivSuche.methods.addDigitalobjekt.call(
      vm,
      { id: 7 },
      {
        id: 3,
        titel: 'Kopie',
        quellen_id: 11,
        dateidatum: '2026-08-26',
      },
    )

    expect(postMock).toHaveBeenCalledWith('/ArchivFiles/getTree', {
      directory: 'archiveingang',
      withFiles: true,
    })
    expect(vm.addDigitalDialogParent).toEqual({ id: 7 })
    expect(vm.addDigitalDialogRow).toEqual(expect.objectContaining({ id: 3 }))
    expect(vm.addDigitalDialogTree).toEqual([{ path: 'archiveingang/neu.pdf' }])
    expect(vm.showAddDigitalDialog).toBe(true)
  })

  it('speichert ein Digitalobjekt und erhöht den Dateizähler', async () => {
    const postMock = vi.fn().mockResolvedValue({ data: {} })
    const vm = {
      $axios: { post: postMock },
      $sendMsg: vi.fn(),
      addDigitalDialogParent: { id: 7 },
      addDigitalDialogRow: null,
      tableData: [{ id: 7, datei_anzahl: 1, archivdatei: { id: 7, count: 1 } }],
      updateDigitalCount: ArchivSuche.methods.updateDigitalCount,
      closeAddDigitalDialog: ArchivSuche.methods.closeAddDigitalDialog,
      showAddDigitalDialog: true,
    }

    await ArchivSuche.methods.saveAddDigitalobjekt.call(vm, {
      titel: 'Neue Datei',
      quellen_id: 11,
      gesperrt: false,
      gesperrt_bis: null,
      dateidatum: '2026-08-26',
      file: 'archiveingang/neu.pdf',
    })

    expect(postMock).toHaveBeenCalledWith('/Digitalobjekte/save', {
      archivobjekt_id: 7,
      titel: 'Neue Datei',
      quellen_id: 11,
      gesperrt: false,
      gesperrt_bis: null,
      dateidatum: '2026-08-26',
      sourcefilepath: 'archiveingang/neu.pdf',
    })
    expect(vm.tableData[0].datei_anzahl).toBe(2)
    expect(vm.showAddDigitalDialog).toBe(false)
  })

  it('löscht ein Digitalobjekt und blendet den leeren Detailbereich aus', async () => {
    const getMock = vi.fn().mockResolvedValue({ data: {} })
    const vm = {
      $axios: { get: getMock },
      $sendMsg: vi.fn(),
      deleteDigitalDialogParent: { id: 7 },
      deleteDigitalDialogRow: { id: 3, titel: 'Datei' },
      tableData: [{ id: 7, datei_anzahl: 1, analogobjekt_anzahl: 0, expanded: true }],
      updateDigitalCount: ArchivSuche.methods.updateDigitalCount,
      closeDeleteDigitalDialog: ArchivSuche.methods.closeDeleteDigitalDialog,
      showDeleteDigitalDialog: true,
    }

    await ArchivSuche.methods.confirmDeleteDigitalobjekt.call(vm)

    expect(getMock).toHaveBeenCalledWith('/Digitalobjekte/delete/3')
    expect(vm.tableData[0].datei_anzahl).toBe(0)
    expect(vm.tableData[0].expanded).toBe(false)
    expect(vm.showDeleteDigitalDialog).toBe(false)
  })

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
