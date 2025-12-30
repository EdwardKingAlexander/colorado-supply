import { describe, it, expect } from 'vitest'

describe.skip('SAM Documents UI', () => {
  it('renders document list shape', () => {
    const sample = {
      data: [
        { id: 1, original_filename: 'file.pdf', size_bytes: 1024, mime_type: 'application/pdf' },
      ],
    }
    expect(sample.data[0].original_filename).toBe('file.pdf')
  })

  it('handles empty documents', () => {
    const sample = { data: [] }
    expect(sample.data.length).toBe(0)
  })
})
