// Re-export all types from index.d.ts
export type { Auth, BreadcrumbItem, NavGroup, NavItem, SharedData, User, AutocompleteOption, DestinatarAutocompleteOption, LocalitateAutocompleteOption, PunctDeLucru, UserPrefs } from './index.d';
export type { TabulatorDataResponse, AwbData, UpdatedAwbResponse, UpdatedAwbResponseData, OrderData, BorderouData, DestinatarData, CostEstimateResponse } from './index.d';
export type { ImportValidationResponse } from './index.d';

// Email validation regex
export const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

// Romanian mobile phone validation regex
// Accepts: +4073xxxxxxx, 0073xxxxxxx, 073xxxxxxx, +40 73xxxxxxx, etc.
// Romanian mobile networks: 072x, 073x, 074x, 075x, 076x, 077x, 078x, 079x
export const romanianMobileRegex = /^(\+?40\s?|0)?7[0-9]\d{7}$/;
