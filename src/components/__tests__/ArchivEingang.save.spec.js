import { describe, expect, it, vi } from 'vitest'
import ArchivEingang, { FilesSelected } from '../ArchivEingang.vue'

function createVm({
  archivOption,
  filesSelected,
  eingang,
  digitalObjekt = {},
  analogObjektAngelegt = false,
}) {
  const postMock = vi.fn(async () => ({ data: {} }))
  const sendMsgMock = vi.fn()
  const refreshTreeMock = vi.fn()
  const resetDataMock = vi.fn()

  const vm = {
    nonPdfSelected: false,
    filesSelected,
    selectedFiles: {},
    eingang,
    archivObjekt: {
      archivOption,
      titel: 'Sammel Titel',
      beschreibung: '',
      status: 1,
      zeitraum_start: '',
      zeitraum_ende: '',
      start_ergaenzung: '',
      ende_ergaenzung: '',
      digitalObjekte: [],
      analogObjekte: [],
    },
    digitalObjekt: {
      dateidatum: '2024',
      quellen_id: 5,
      gesperrt: false,
      gesperrt_bis: null,
      ...digitalObjekt,
    },
    analogObjekt: {
      archiv_id: 'AN-0001',
      gesperrt: false,
      gesperrt_bis: '2050',
    },
    analogObjektAngelegt,
    collectSelected: ArchivEingang.methods.collectSelected,
    formatDokumentDatum: ArchivEingang.methods.formatDokumentDatum,
    $axios: { post: postMock },
    $sendMsg: sendMsgMock,
    refreshTree: refreshTreeMock,
    resetData: resetDataMock,
  }

  // Markiert alle Dateien im eingang-Baum als ausgewählt.
  const markSelected = (nodes) => {
    for (const node of nodes) {
      if (node.type === 'file') {
        vm.selectedFiles[node.path] = true
      }
      if (node.children) {
        markSelected(node.children)
      }
    }
  }
  markSelected(eingang)

  return { vm, postMock, sendMsgMock, refreshTreeMock, resetDataMock }
}

function getCallsByUrl(postMock, url) {
  return postMock.mock.calls.filter((call) => call[0] === url)
}

describe('ArchivEingang save()', () => {
  it('CombineFiles: fasst mehrere ausgewählte Dateien zu einem Digitalobjekt zusammen', async () => {
    const { vm, postMock, sendMsgMock, refreshTreeMock, resetDataMock } = createVm({
      archivOption: 'CombineFiles',
      filesSelected: FilesSelected.Multiple,
      eingang: [
        {
          type: 'directory',
          children: [
            { type: 'file', path: 'archiveingang/a.pdf', mimeType: 'application/pdf' },
            { type: 'file', path: 'archiveingang/b.pdf', mimeType: 'application/pdf' },
          ],
        },
      ],
    })

    await ArchivEingang.methods.save.call(vm)

    expect(vm.archivObjekt.sourceFiles).toEqual(['archiveingang/a.pdf', 'archiveingang/b.pdf'])
    expect(vm.archivObjekt.digitalObjekte).toEqual([
      expect.objectContaining({
        dateidatum: '2024-01-01',
        titel: 'Sammel Titel',
        quellen_id: 5,
        sourcefilepath: '',
      }),
    ])

    const saveCalls = getCallsByUrl(postMock, '/Archivobjekte/saveOrUpdate')
    expect(saveCalls).toHaveLength(1)
    expect(saveCalls[0][1].archivObjekt.analogObjekte).toBeUndefined()
    expect(sendMsgMock).toHaveBeenCalledWith(false, 'Archivobjekt erfolgreich gespeichert.')
    expect(refreshTreeMock).toHaveBeenCalledOnce()
    expect(resetDataMock).toHaveBeenCalledOnce()
  })

  it('SingleFile: speichert eine einzelne ausgewählte Datei direkt', async () => {
    const { vm, postMock } = createVm({
      archivOption: 'CombineFiles',
      filesSelected: FilesSelected.Single,
      eingang: [{ type: 'file', path: 'archiveingang/einzel.pdf', mimeType: 'application/pdf' }],
    })

    await ArchivEingang.methods.save.call(vm)

    expect(vm.archivObjekt.archivOption).toBe('SingleFile')
    expect(vm.archivObjekt.digitalObjekte).toEqual([
      expect.objectContaining({ sourcefilepath: 'archiveingang/einzel.pdf' }),
    ])
    expect(getCallsByUrl(postMock, '/Archivobjekte/saveOrUpdate')).toHaveLength(1)
  })

  it('bricht ab, wenn nicht-PDF-Dateien zusammengefasst werden sollen', async () => {
    const { vm, postMock, sendMsgMock } = createVm({
      archivOption: 'CombineFiles',
      filesSelected: FilesSelected.Multiple,
      eingang: [
        {
          type: 'directory',
          children: [
            { type: 'file', path: 'archiveingang/a.pdf', mimeType: 'application/pdf' },
            { type: 'file', path: 'archiveingang/b.jpg', mimeType: 'image/jpeg' },
          ],
        },
      ],
    })

    await ArchivEingang.methods.save.call(vm)

    expect(sendMsgMock).toHaveBeenCalledWith(
      true,
      'Es können nur PDF-Dateien zusammengefasst werden.',
    )
    expect(getCallsByUrl(postMock, '/Archivobjekte/saveOrUpdate')).toHaveLength(0)
  })

  it('übernimmt das angelegte Analogobjekt beim Speichern', async () => {
    const { vm, postMock } = createVm({
      archivOption: 'CombineFiles',
      filesSelected: FilesSelected.Multiple,
      eingang: [
        {
          type: 'directory',
          children: [
            { type: 'file', path: 'archiveingang/a.pdf', mimeType: 'application/pdf' },
            { type: 'file', path: 'archiveingang/b.pdf', mimeType: 'application/pdf' },
          ],
        },
      ],
      analogObjektAngelegt: true,
    })

    await ArchivEingang.methods.save.call(vm)

    const saveCalls = getCallsByUrl(postMock, '/Archivobjekte/saveOrUpdate')
    expect(saveCalls[0][1].archivObjekt.analogObjekte).toEqual([
      expect.objectContaining({ archiv_id: 'AN-0001', gesperrt_bis: null }),
    ])
  })

  it('saveOptionsApplied: übernimmt Titel und Quellen pro Datei und speichert', async () => {
    const { vm, postMock } = createVm({
      archivOption: 'CombineFiles',
      filesSelected: FilesSelected.Multiple,
      eingang: [
        {
          type: 'directory',
          children: [
            { type: 'file', path: 'archiveingang/a.pdf', mimeType: 'application/pdf' },
            { type: 'file', path: 'archiveingang/b.jpg', mimeType: 'image/jpeg' },
          ],
        },
      ],
    })
    vm.filesToSave = ['archiveingang/a.pdf', 'archiveingang/b.jpg']
    vm.save = ArchivEingang.methods.save

    await ArchivEingang.methods.saveOptionsApplied.call(vm, {
      titles: ['Titel A', 'Titel B'],
      quellen_ids: [1, 2],
      gesperrt: [false, true],
      gesperrt_bis: [null, '2030'],
      dateidaten: ['', ''],
    })

    expect(vm.archivObjekt.archivOption).toBe('MultipleFiles')
    expect(vm.archivObjekt.digitalObjekte).toEqual([
      expect.objectContaining({
        titel: 'Titel A',
        sourcefilepath: 'archiveingang/a.pdf',
        quellen_id: 1,
        gesperrt_bis: null,
      }),
      expect.objectContaining({
        titel: 'Titel B',
        sourcefilepath: 'archiveingang/b.jpg',
        quellen_id: 2,
        gesperrt_bis: '2030',
      }),
    ])
    expect(vm.showOptionDialog).toBe(false)
    expect(getCallsByUrl(postMock, '/Archivobjekte/saveOrUpdate')).toHaveLength(1)
  })
})
