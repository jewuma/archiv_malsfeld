import { describe, expect, it, vi } from 'vitest'
import DigitalObjekte from '../DigitalObjekte.vue'

function createVm() {
  return {
    eingang: [
      {
        type: 'directory',
        children: [
          { type: 'file', path: 'archiveingang/a.pdf' },
          { type: 'file', path: 'archiveingang/b.jpg' },
        ],
      },
    ],
    selectedFiles: {},
    filesToAdd: [],
    showAddDateiDialog: false,
    showSaveOptions: false,
    lokal: [],
    $sendMsg: vi.fn(),
    collectSelected: DigitalObjekte.methods.collectSelected,
    closeSaveOptions: DigitalObjekte.methods.closeSaveOptions,
  }
}

describe('DigitalObjekte addFiles()', () => {
  it('öffnet den Datei-hinzufügen-Dialog und setzt die Auswahl zurück', () => {
    const vm = createVm()
    vm.selectedFiles = { 'archiveingang/a.pdf': true }

    DigitalObjekte.methods.addFiles.call(vm)

    expect(vm.showAddDateiDialog).toBe(true)
    expect(vm.selectedFiles).toEqual({})
  })

  it('öffnet für mehrere Dateien die Speicheroptionen', () => {
    const vm = createVm()
    vm.selectedFiles = {
      'archiveingang/a.pdf': true,
      'archiveingang/b.jpg': true,
    }

    DigitalObjekte.methods.filesSelectedForAdd.call(vm)

    expect(vm.showAddDateiDialog).toBe(false)
    expect(vm.showSaveOptions).toBe(true)
    expect(vm.filesToAdd).toEqual(['archiveingang/a.pdf', 'archiveingang/b.jpg'])
  })

  it('übernimmt Dateinamen, Einzeltitel und Prefix aus den Speicheroptionen', () => {
    const vm = createVm()
    vm.filesToAdd = ['archiveingang/a.pdf', 'archiveingang/b.jpg']

    DigitalObjekte.methods.saveOptionsApplied.call(vm, {
      archivOption: 'selectTitlePerFile',
      newFilePrefix: true,
      filePrefix: 'PFX',
      titles: ['Titel A', 'Titel B'],
    })

    expect(vm.lokal).toEqual([
      expect.objectContaining({ name: 'PFX_00001.pdf', titel: 'Titel A' }),
      expect.objectContaining({ name: 'PFX_00002.jpg', titel: 'Titel B' }),
    ])
    expect(vm.showSaveOptions).toBe(false)
    expect(vm.filesToAdd).toEqual([])
  })
})
