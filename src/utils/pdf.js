/**
 * ==========================================
 * PDF Utility
 * Einheitliches Handling für:
 * - Upload (File)
 * - Base64
 * - API (Backend)
 * - Drag & Drop / DB
 * ==========================================
 */

/**
 * @typedef {Object} PdfValue
 * @property {string|null} id
 * @property {string|null} fileName
 * @property {string|null} apiPath
 * @property {File|null} file
 * @property {string|null} fileContent   // Base64
 * @property {"upload"|"database"|"fileList"|"base64"|null} origin
 * @property {string|null} originalFileName
 */

export function createPdf({
  action = 'keep',
  file = null,
  fileName = null,
  apiPath = null,
  fileContent = null,
} = {}) {
  return {
    valid: !!apiPath || !!fileContent,
    action,
    file,
    fileName,
    apiPath,
    fileContent,
  }
}

/**
 * 🔄 Base64 → Blob
 */
export function base64ToBlob(base64, type = 'application/pdf') {
  const clean = base64.includes(',') ? base64.split(',')[1] : base64

  const byteCharacters = atob(clean)
  const byteNumbers = Array.from(byteCharacters, (c) => c.charCodeAt(0))

  return new Blob([new Uint8Array(byteNumbers)], { type })
}

/**
 * 🔄 File → Base64
 */
export function fileToBase64(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => resolve(reader.result.split(',')[1])
    reader.onerror = reject
    reader.readAsDataURL(file)
  })
}

/**
 * PDF anzeigen (egal woher)
 */
export async function openPdf(pdfValue, axiosInstance) {
  if (!pdfValue || !pdfValue.valid) {
    console.warn('Kein gültiges PDF vorhanden')
    return
  }

  try {
    let blob

    // Base64
    if (pdfValue.fileContent) {
      blob = base64ToBlob(pdfValue.fileContent)
    }

    // Upload (File)
    else if (pdfValue.file instanceof File) {
      blob = pdfValue.file
    }

    // API
    else if (pdfValue.apiPath) {
      const response = await axiosInstance.get(pdfValue.apiPath, {
        responseType: 'blob',
      })
      blob = new Blob([response.data], { type: 'application/pdf' })
    } else {
      console.warn('Keine gültige PDF-Quelle')
      return
    }

    const url = window.URL.createObjectURL(blob)
    window.open(url, '_blank')
  } catch (error) {
    console.error('Fehler beim Laden der PDF:', error)
  }
}

/**
 * 🔄 Backend-Payload erzeugen
 * (für dein {value, action, valueType} Modell)
 */
export async function buildPdfPayload(pdf, originalPdf) {
  // ❌ gelöscht
  if (!pdf && originalPdf) {
    return { action: 'delete', value: null, valueType: null }
  }

  // ➖ nichts vorhanden
  if (!pdf || !pdf.valid) {
    return { action: 'keep', value: null, valueType: null }
  }

  // 📁 neuer Upload
  if (pdf.file) {
    const base64 = await fileToBase64(pdf.file)
    return {
      action: 'replace',
      value: base64,
      valueType: 'base64',
    }
  }

  // 🧾 Base64 vorhanden
  if (pdf.fileContent) {
    return {
      action: 'replace',
      value: pdf.fileContent,
      valueType: 'base64',
    }
  }

  // 📄 Datei geändert
  if (pdf.fileName !== originalPdf?.fileName) {
    return {
      action: 'replace',
      value: pdf.fileName,
      valueType: 'filename',
    }
  }

  // ✔ unverändert
  return {
    action: 'keep',
    value: pdf.fileName,
    valueType: 'filename',
  }
}
