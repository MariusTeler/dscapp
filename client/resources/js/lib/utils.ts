import { InertiaLinkProps } from '@inertiajs/react';
import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function isSameUrl(
    url1: NonNullable<InertiaLinkProps['href']>,
    url2: NonNullable<InertiaLinkProps['href']>,
) {
    return resolveUrl(url1) === resolveUrl(url2);
}

export function resolveUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

/**
 * Builds an anchored regex from a pattern string.
 * Wraps the pattern with ^ and $ anchors if not already present.
 */
export function buildAnchoredRegex(pattern: string): RegExp {
    const anchored = (pattern.startsWith('^') ? '' : '^') + pattern + (pattern.endsWith('$') ? '' : '$');
    return new RegExp(anchored);
}
