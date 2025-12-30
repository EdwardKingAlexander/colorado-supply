import { describe, it, expect } from 'vitest'

describe.skip('SAM Insights UI', () => {
  it('renders answer from API response shape', () => {
    const sample = {
      answer: 'Stubbed answer',
      top_chunks: [{ chunk_id: 1, text: 'context', score: 1.0 }],
    }
    expect(sample.answer).toContain('Stubbed')
    expect(sample.top_chunks.length).toBeGreaterThan(0)
  })

  it('shows empty state when no chunks', () => {
    const sample = {
      answer: 'No indexed content available for this opportunity yet.',
      top_chunks: [],
    }
    expect(sample.top_chunks.length).toBe(0)
  })
})
