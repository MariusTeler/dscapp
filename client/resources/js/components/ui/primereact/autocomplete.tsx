import { useState, useRef, useEffect } from "react"
import { AutoComplete as PrimeAutoComplete } from 'primereact/autocomplete'
import type { AutocompleteOption } from "@/types"
import axios from '@/lib/axios'

import { cn } from "@/lib/utils"

interface AutocompleteProps<T extends AutocompleteOption = AutocompleteOption> {
  id?: string
  text: string | null
  onChange: (text: string) => void
  onSelect: (option: T) => void
  searchUrl: string
  placeholder?: string
  className?: string
  invalid?: boolean
  disabled?: boolean
  minSearchLength?: number
  debounceMs?: number
  onOpen?: () => void
  onClose?: () => void
  onNoResults?: () => void
  onError?: (error: string) => void
}

export function Autocomplete<T extends AutocompleteOption = AutocompleteOption>({
  id,
  text,
  onChange,
  onSelect,
  searchUrl,
  placeholder = '...',
  className,
  invalid = false,
  disabled = false,
  minSearchLength = 3,
  debounceMs = 300,
  onOpen,
  onClose,
  onNoResults,
  onError,
}: AutocompleteProps<T>) {
  const [options, setOptions] = useState<T[]>([])
  const abortControllerRef = useRef<AbortController | null>(null)

  // Passthrough theme configuration

  const TRANSITIONS = {
    overlay: {
        timeout: 150,
        cn: {
            enter: 'opacity-0 scale-75',
            enterActive: 'opacity-100 !scale-100 transition-transform transition-opacity duration-150 ease-in',
            exit: 'opacity-100',
            exitActive: '!opacity-0 transition-opacity duration-150 ease-linear'
        }
    }
  };

  const theme = {
    root: { className: "relative w-full" },
    input: { 
      root: {
        className: cn(
          "w-full h-8 px-3 py-2 pr-10 text-base rounded-md border bg-background",
          '[&:focus-within]:outline-none [&:focus-within]:shadow-[0_0_0_0.2rem_rgba(191,219,254,1)] dark:[&:focus-within]:shadow-[0_0_0_0.2rem_rgba(147,197,253,0.5)]',
          "disabled:cursor-not-allowed disabled:opacity-50",
          invalid ? "border-red-500" : "border-input", className
        )
      }
    },
    panel: { 
      className: "absolute left-0 z-[9999] top-full bg-background border border-t-0 border-input rounded-b-md shadow-lg"
    },
    list: { 
      className: "max-h-[200px] overflow-auto p-1"
    },
    item: ({ context }: { context: { selected: boolean; disabled: boolean } }) => ({
      className: cn(
          'cursor-pointer font-normal overflow-hidden relative whitespace-nowrap',
          'm-0 px-3 py-1 border-0 transition-shadow duration-200 rounded-none',
          'text-gray-700 hover:bg-gray-200 dark:text-white/80 dark:hover:bg-gray-800',
          context.selected && 'bg-blue-100 text-blue-700 dark:bg-blue-400 dark:text-white/80',
          context.disabled && 'opacity-50 cursor-not-allowed'
        )
    }),
    loadingIcon: {
      className: "absolute right-3 top-1/2 -translate-y-1/2 animate-spin h-4 w-4 border-2 border-current border-t-transparent rounded-full"
    },
    transition: TRANSITIONS.overlay
  }

  // Search function for PrimeReact AutoComplete
  const searchOptions = async (event: { query: string }) => {
    const searchTerm = event.query

    if (searchTerm.length < minSearchLength) {
      setOptions([])
      if (onNoResults) {
        onNoResults()
      }
      return
    }

    // Cancel any pending request
    if (abortControllerRef.current) {
      abortControllerRef.current.abort()
    }

    // Create new abort controller
    abortControllerRef.current = new AbortController()
    console.log('[Autocomplete] Searching for:', searchTerm)

    try {
      const response = await axios.get(`${searchUrl}?q=${encodeURIComponent(searchTerm)}`, {
        headers: {
          'Content-Type': 'application/json',
        },
        signal: abortControllerRef.current.signal,
      })

      const data = response.data
      const results = data.data || data || []

      //console.log('[Autocomplete] Raw results:', results)

      // Normalize results - spread original item first to preserve ALL fields
      const normalized = (Array.isArray(results) ? results : []).map((r: unknown) => {
        const item = r as Record<string, unknown>
        const value = (item?.value as unknown) ?? (item?.id as unknown) ?? ''
        const numeric = typeof value === 'number' ? value : Number(value || 0)
        
        // Spread all original fields first, then override only the base fields
        return {
          ...item,
          value: Number.isFinite(numeric) ? numeric : 0,
          text: (item?.text as string) ?? (item?.name as string) ?? '',
          option: (item?.option as string) ?? (item?.label as string) ?? (item?.text as string) ?? (item?.name as string) ?? ''
        } as T
      })

      setOptions(normalized)
      //console.log('[Autocomplete] Normalized options:', options)
      
      // Call onNoResults if no results found
      if (normalized.length === 0 && onNoResults) {
        onNoResults()
      }
    } catch (error) {
      if (axios.isAxiosError(error)) {
        console.error('Search failed:', error.response?.statusText || error.message)
        setOptions([])
        if (onError) {
          onError(`Căutarea a eșuat: ${error.response?.statusText || error.message}`)
        }
        if (onNoResults) {
          onNoResults()
        }
      } else if (error instanceof Error && error.name !== 'AbortError') {
        console.error('Search error:', error)
        setOptions([])
        if (onError) {
          onError(error.message || 'Eroare la căutare')
        }
      }
    }
  }

  // Handle option selection
  const handleSelect = (e: { value: unknown }) => {
    //console.log('[Autocomplete] handleSelect called with:', e.value)
    const option = typeof e.value === 'string' ? null : (e.value as T)
    //console.log('[Autocomplete] Parsed option:', option)
    if (option) {
      onSelect(option)
    }
  }

  // Handle text input change
  const handleChange = (e: { value: unknown }) => {
    const newValue = typeof e.value === 'string' ? e.value : (e.value as T).text
    console.log('[Autocomplete] handleChange called with:', newValue)
    onChange(newValue)
  }

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      if (abortControllerRef.current) {
        abortControllerRef.current.abort()
      }
    }
  }, [])

  return (
    <div className="relative w-full">
      <PrimeAutoComplete
        inputId={id}
        value={text ?? ''}
        suggestions={options as unknown[]}
        completeMethod={searchOptions}
        onChange={handleChange}
        onSelect={handleSelect}
        onShow={onOpen}
        onHide={onClose}
        onClear={onOpen}
        field="option"
        placeholder={placeholder}
        disabled={disabled}
        invalid={invalid}
        delay={debounceMs}
        unstyled
        appendTo="self"
        pt={theme}
      />
    </div>
  )
}

export default Autocomplete
export type { AutocompleteOption, AutocompleteProps }
