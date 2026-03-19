"use client";

import * as React from "react";
import { Check, ChevronDown, Search } from "lucide-react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

export interface ComboboxOption {
  value: string | number;
  label: string;
  description?: string;
}

export interface ComboboxProps {
  placeholder?: string;
  emptyText?: string;
  searchPlaceholder?: string;
  value?: string | number;
  onValueChange?: (value: string | number) => void;
  options: ComboboxOption[];
  className?: string;
  disabled?: boolean;
  loading?: boolean;
  onSearch?: (search: string) => void;
  searchValue?: string;
  error?: string;
}

export function Combobox({
  placeholder = "Select option...",
  emptyText = "No option found.",
  searchPlaceholder = "Search...",
  value,
  onValueChange,
  options,
  className,
  disabled,
  loading,
  onSearch,
  searchValue,
  error,
}: ComboboxProps) {
  const [open, setOpen] = React.useState(false);
  const [internalSearchValue, setInternalSearchValue] = React.useState("");
  const dropdownRef = React.useRef<HTMLDivElement>(null);

  const selectedOption = options.find((option) => option.value === value);
  const currentSearchValue = searchValue ?? internalSearchValue;

  // Filter options based on search
  const filteredOptions = React.useMemo(() => {
    if (!onSearch && currentSearchValue) {
      return options.filter(option =>
        option.label.toLowerCase().includes(currentSearchValue.toLowerCase()) ||
        option.description?.toLowerCase().includes(currentSearchValue.toLowerCase())
      );
    }
    return options;
  }, [options, currentSearchValue, onSearch]);

  const handleSearch = React.useCallback((search: string) => {
    setInternalSearchValue(search);
    onSearch?.(search);
  }, [onSearch]);

  const handleSelect = React.useCallback((option: ComboboxOption) => {
    onValueChange?.(option.value);
    setOpen(false);
  }, [onValueChange]);

  const handleInputKeyDown = React.useCallback((e: React.KeyboardEvent) => {
    if (e.key === 'Escape') {
      setOpen(false);
    }
  }, []);

  const handleToggle = React.useCallback(() => {
    if (!disabled) {
      setOpen(!open);
    }
  }, [disabled, open]);

  // Close dropdown when clicking outside
  React.useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setOpen(false);
      }
    };

    if (open) {
      document.addEventListener('mousedown', handleClickOutside);
      return () => document.removeEventListener('mousedown', handleClickOutside);
    }
  }, [open]);

  return (
    <div className={cn("relative", className)} ref={dropdownRef}>
      <Button
        type="button"
        variant="outline"
        role="combobox"
        aria-expanded={open}
        onClick={handleToggle}
        className={cn(
          "w-full justify-between h-auto min-h-[40px] px-3 py-2",
          error && "border-red-500",
          disabled && "opacity-50 cursor-not-allowed"
        )}
        disabled={disabled}
      >
        {selectedOption ? (
          <div className="flex flex-col items-start text-left">
            <span className="font-medium">{selectedOption.label}</span>
            {selectedOption.description && (
              <span className="text-xs text-muted-foreground">
                {selectedOption.description}
              </span>
            )}
          </div>
        ) : (
          <span className="text-muted-foreground">{placeholder}</span>
        )}
        <ChevronDown className={cn("h-4 w-4 shrink-0 opacity-50 transition-transform", open && "rotate-180")} />
      </Button>

      {open && (
        <div className="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-hidden">
          <div className="p-2 border-b">
            <div className="relative">
              <Search className="absolute left-2 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
              <Input
                placeholder={searchPlaceholder}
                value={currentSearchValue}
                onChange={(e) => handleSearch(e.target.value)}
                onKeyDown={handleInputKeyDown}
                className="pl-8"
                autoFocus
              />
            </div>
          </div>

          <div className="max-h-48 overflow-y-auto">
            {loading ? (
              <div className="flex items-center justify-center py-6">
                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-primary"></div>
              </div>
            ) : filteredOptions.length > 0 ? (
              <div className="py-1">
                {filteredOptions.map((option) => (
                  <button
                    key={option.value}
                    type="button"
                    onClick={() => handleSelect(option)}
                    className={cn(
                      "w-full px-3 py-2 text-left hover:bg-gray-100 focus:bg-gray-100 focus:outline-none flex items-center",
                      value === option.value && "bg-gray-50"
                    )}
                  >
                    <Check
                      className={cn(
                        "mr-2 h-4 w-4 text-primary",
                        value === option.value ? "opacity-100" : "opacity-0"
                      )}
                    />
                    <div className="flex flex-col">
                      <span className="font-medium">{option.label}</span>
                      {option.description && (
                        <span className="text-xs text-gray-500">
                          {option.description}
                        </span>
                      )}
                    </div>
                  </button>
                ))}
              </div>
            ) : (
              <div className="py-6 text-center text-gray-500 text-sm">
                {emptyText}
              </div>
            )}
          </div>
        </div>
      )}

      {error && (
        <p className="text-red-500 text-sm mt-1">{error}</p>
      )}
    </div>
  );
}
