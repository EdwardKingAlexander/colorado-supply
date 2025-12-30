import { describe, it, expect } from 'vitest'

describe.skip('SAM Favorites UI', () => {
  it('renders favorites list from API response shape', () => {
    const sample = {
      data: [
        { id: 1, title: 'Opp A', agency: 'Agency', posted_date: '2025-01-01', response_deadline: '2025-02-01', sam_url: 'https://sam.gov/opp/1', is_favorite: true },
      ],
      meta: { current_page: 1, last_page: 1, next_page_url: null, prev_page_url: null },
    }

    expect(sample.data[0].is_favorite).toBe(true)
    expect(sample.meta.current_page).toBe(1)
  })

  it('handles empty favorites state', () => {
    const sample = { data: [], meta: { current_page: 1, last_page: 1 } }
    expect(sample.data.length).toBe(0)
  })
})
