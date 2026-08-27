import { describe, expect, it, vi } from 'vitest'
import DigitalObjekte from '../DigitalObjekte.vue'

function createVm() {
  const emitMock = vi.fn()
  const vm = {
    modelValue: [],
    selectedFiles: {},
    get lokal() {
      return this.modelValue
    },
    set lokal(value) {
      this.modelValue = value
      emitMock(value)
    },
  }
  return { vm, emitMock }
}

describe('DigitalObjekte', () => {
  it('addFiles() übernimmt ausgewählte Dateien in die lokale Liste', () => {
    const { vm, emitMock } = createVm()
    const fileList = [
      { name: 'a.pdf', type: 'application/pdf', size: 2048 },
      { name: 'b.jpg', type: 'image/jpeg', size: 512 },
    ]

    DigitalObjekte.methods.addFiles.call(vm, fileList)

    expect(vm.lokal).toEqual([
      expect.objectContaining({ id: null, name: 'a.pdf', typ: 'application/pdf', groesse: 2048 }),
      expect.objectContaining({ id: null, name: 'b.jpg', typ: 'image/jpeg', groesse: 512 }),
    ])
    expect(emitMock).toHaveBeenCalledWith(vm.lokal)
  })

  it('entfernen() entfernt eine Datei anhand des Index', () => {
    const { vm } = createVm()
    DigitalObjekte.methods.addFiles.call(vm, [
      { name: 'a.pdf', type: 'application/pdf', size: 2048 },
      { name: 'b.jpg', type: 'image/jpeg', size: 512 },
    ])

    DigitalObjekte.methods.entfernen.call(vm, 0)

    expect(vm.lokal).toEqual([expect.objectContaining({ name: 'b.jpg' })])
  })

  it('fileSelected() verwaltet die Auswahl von Posteingangsdateien', () => {
    const { vm } = createVm()

    DigitalObjekte.methods.fileSelected.call(vm, 'archiveingang/a.pdf', true)
    expect(vm.selectedFiles).toEqual({ 'archiveingang/a.pdf': '' })

    DigitalObjekte.methods.fileSelected.call(vm, 'archiveingang/a.pdf', false)
    expect(vm.selectedFiles).toEqual({})
  })

  it('collectSelected() sammelt die Pfade ausgewählter Dateien aus dem Baum', () => {
    const { vm } = createVm()
    vm.selectedFiles = { 'archiveingang/a.pdf': '' }
    vm.collectSelected = DigitalObjekte.methods.collectSelected
    const tree = [
      {
        type: 'directory',
        children: [
          { type: 'file', path: 'archiveingang/a.pdf' },
          { type: 'file', path: 'archiveingang/b.jpg' },
        ],
      },
    ]

    const result = DigitalObjekte.methods.collectSelected.call(vm, tree)

    expect(result).toEqual(['archiveingang/a.pdf'])
  })

  it('formatSize() formatiert Bytes, Kilobytes und Megabytes', () => {
    expect(DigitalObjekte.methods.formatSize(500)).toBe('500 B')
    expect(DigitalObjekte.methods.formatSize(2048)).toBe('2.0 KB')
    expect(DigitalObjekte.methods.formatSize(5 * 1024 * 1024)).toBe('5.0 MB')
  })
})
