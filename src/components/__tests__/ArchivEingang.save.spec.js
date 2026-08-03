import { describe, expect, it, vi } from 'vitest'
import ArchivEingang from '../ArchivEingang.vue'

function createVm({ archivOption, selectedFiles, saveOptions = {}, analogObjektAngelegt = false }) {
  const postMock = vi.fn(async () => ({ data: {} }))
  const sendMsgMock = vi.fn()
  const refreshTreeMock = vi.fn()
  const resetDataMock = vi.fn()

  const vm = {
    nonPdfSelected: false,
    eingang: [],
    collectSelected: vi.fn(() => selectedFiles),
    saveOptions: {
      archivOption,
      filePrefix: '',
      newFilePrefix: false,
      titles: [],
      ...saveOptions,
    },
    archivObjekt: {
      id: 0,
      ort_id: 4,
      zeitraum_start: '',
      zeitraum_ende: '',
      start_ergaenzung: '',
      ende_ergaenzung: '',
      themen_id: 2,
      status: 1,
      titel: 'Default Titel',
      beschreibung: '',
      dateiPfad: '',
    },
    analogObjektAngelegt,
    analogObjekt: {
      id: 123,
      archiv_id: 'AN-0001',
      objekttyp_id: 1,
      gesperrt: false,
      gesperrt_bis: '2050',
      lagerort_id: 1,
      regal_id: null,
      fach_id: null,
      platz_id: null,
      digitalisiert: 2,
    },
    digitalObjekt: {
      pfad: '',
      dateiname: '',
      objekttyp_id: 1,
      quellen_id: 0,
      gesperrt: false,
      gesperrt_bis: '2050',
      dateidatum: '2024',
    },
    storeBasePath: 'Ort/',
    selectedSpeicherpfad: 'Unterordner',
    speicherpfad: 'Ort/Unterordner/2024_0101_Test_AN-0001.pdf',
    formatDokumentDatum: ArchivEingang.methods.formatDokumentDatum,
    $axios: {
      post: postMock,
    },
    $sendMsg: sendMsgMock,
    refreshTree: refreshTreeMock,
    resetData: resetDataMock,
  }

  return { vm, postMock, sendMsgMock, refreshTreeMock, resetDataMock }
}

function getCallsByUrl(postMock, url) {
  return postMock.mock.calls.filter((call) => call[0] === url)
}

describe('ArchivEingang save() Speicheroptionen', () => {
  it('CombineFilesToOneArchivObjekt: speichert kombiniert und legt ein Digitalobjekt an', async () => {
    const { vm, postMock, sendMsgMock, refreshTreeMock, resetDataMock } = createVm({
      archivOption: 'CombineFilesToOneArchivObjekt',
      selectedFiles: ['eingang/a.pdf', 'eingang/b.pdf'],
    })

    await ArchivEingang.methods.save.call(vm)

    const saveFilesCalls = getCallsByUrl(postMock, '/ArchivFiles/saveFiles')
    expect(saveFilesCalls).toHaveLength(1)
    expect(saveFilesCalls[0][1].combine).toBe(true)

    const createCalls = getCallsByUrl(postMock, '/Archiveingang/createOrUpdate')
    expect(createCalls).toHaveLength(1)
    expect(createCalls[0][1].digitalobjekte).toHaveLength(1)
    expect(createCalls[0][1].digitalobjekte[0].dateiname).toBe('2024_0101_Test_AN-0001.pdf')

    expect(getCallsByUrl(postMock, '/ArchivFiles/finalizeSavedFiles')).toHaveLength(1)
    expect(getCallsByUrl(postMock, '/ArchivFiles/cleanupSavedFile')).toHaveLength(0)
    expect(sendMsgMock).toHaveBeenCalledWith(false, 'Dateien gespeichert')
    expect(refreshTreeMock).toHaveBeenCalledOnce()
    expect(resetDataMock).toHaveBeenCalledOnce()
  })

  it('newArchivObjectPerFile: legt pro Datei ein Archivobjekt an', async () => {
    const { vm, postMock } = createVm({
      archivOption: 'newArchivObjectPerFile',
      selectedFiles: ['eingang/a.pdf', 'eingang/b.jpg'],
      saveOptions: {
        newFilePrefix: true,
        filePrefix: 'PFX',
      },
    })

    await ArchivEingang.methods.save.call(vm)

    const saveFilesCalls = getCallsByUrl(postMock, '/ArchivFiles/saveFiles')
    expect(saveFilesCalls).toHaveLength(1)
    expect(saveFilesCalls[0][1].combine).toBe(false)

    const createCalls = getCallsByUrl(postMock, '/Archiveingang/createOrUpdate')
    expect(createCalls).toHaveLength(2)
    expect(createCalls[0][1].digitalobjekte[0].dateiname).toBe('PFX_00001.pdf')
    expect(createCalls[1][1].digitalobjekte[0].dateiname).toBe('PFX_00002.jpg')
  })

  it('selectTitlePerFile: verwendet Titel pro Datei', async () => {
    const { vm, postMock } = createVm({
      archivOption: 'selectTitlePerFile',
      selectedFiles: ['eingang/a.pdf', 'eingang/b.pdf'],
      saveOptions: {
        titles: ['Titel A', 'Titel B'],
      },
    })

    await ArchivEingang.methods.save.call(vm)

    const createCalls = getCallsByUrl(postMock, '/Archiveingang/createOrUpdate')
    expect(createCalls).toHaveLength(2)
    expect(createCalls[0][1].archivobjekt.titel).toBe('Titel A')
    expect(createCalls[1][1].archivobjekt.titel).toBe('Titel B')
  })

  it('saveAllFilesToOneArchivObject: speichert ein Archivobjekt mit mehreren Digitalobjekten', async () => {
    const { vm, postMock } = createVm({
      archivOption: 'saveAllFilesToOneArchivObject',
      selectedFiles: ['eingang/a.pdf', 'eingang/b.png', 'eingang/c.tif'],
      saveOptions: {
        newFilePrefix: true,
        filePrefix: 'SAMMEL',
      },
      analogObjektAngelegt: true,
    })

    await ArchivEingang.methods.save.call(vm)

    const createCalls = getCallsByUrl(postMock, '/Archiveingang/createOrUpdate')
    expect(createCalls).toHaveLength(1)
    expect(createCalls[0][1].digitalobjekte).toHaveLength(3)
    expect(createCalls[0][1].digitalobjekte[0].dateiname).toBe('SAMMEL_00001.pdf')
    expect(createCalls[0][1].digitalobjekte[1].dateiname).toBe('SAMMEL_00002.png')
    expect(createCalls[0][1].digitalobjekte[2].dateiname).toBe('SAMMEL_00003.tif')
    expect(createCalls[0][1].analogobjekte).toHaveLength(1)
  })
})
