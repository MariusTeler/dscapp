import * as React from "react"
import { Card as PrimeCard, type CardProps } from 'primereact/card';
import { cn } from "@/lib/utils"

const CARD_THEME = {
  root: 'bg-card text-card-foreground flex flex-col gap-2 rounded-xl border py-2 shadow-sm',
  header: 'flex flex-col gap-1.5 px-6',
  body: 'px-6',
  title: 'leading-none font-semibold',
  subTitle: 'text-muted-foreground text-sm',
  footer: 'flex items-center px-6'
};

interface CustomCardProps extends Omit<CardProps, 'className'> {
  className?: string;
  children?: React.ReactNode;
}

function Card({ className, children, ...props }: CustomCardProps) {
  return (
    <PrimeCard
      className={cn(CARD_THEME.root, className)}
      pt={{
        root: { className: '' },
        body: { className: '' },
        title: { className: '' },
        subTitle: { className: '' },
        header: { className: '' },
        footer: { className: '' }
      }}
      {...props}
    >
      {children}
    </PrimeCard>
  );
}

function CardHeader({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      className={cn(CARD_THEME.header, className)}
      {...props}
    />
  );
}

function CardTitle({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      className={cn(CARD_THEME.title, className)}
      {...props}
    />
  );
}

function CardDescription({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      className={cn(CARD_THEME.subTitle, className)}
      {...props}
    />
  );
}

function CardContent({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      className={cn(CARD_THEME.body, className)}
      {...props}
    />
  );
}

function CardFooter({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      className={cn(CARD_THEME.footer, className)}
      {...props}
    />
  );
}

export { Card, CardHeader, CardFooter, CardTitle, CardDescription, CardContent }
