import type {
    CareerStatus,
    CareerType,
    LearningStatus,
    NoteColor,
    ProjectColor,
    ProjectStatus,
    TaskPriority,
    TaskStatus,
} from '@/types/portal';

export const projectColorClasses: Record<
    ProjectColor,
    {
        dot: string;
        text: string;
        soft: string;
        border: string;
        bar: ProjectColor;
    }
> = {
    indigo: {
        dot: 'bg-indigo-500',
        text: 'text-indigo-600 dark:text-indigo-300',
        soft: 'bg-indigo-500/10',
        border: 'border-indigo-500/30',
        bar: 'indigo',
    },
    emerald: {
        dot: 'bg-emerald-500',
        text: 'text-emerald-600 dark:text-emerald-300',
        soft: 'bg-emerald-500/10',
        border: 'border-emerald-500/30',
        bar: 'emerald',
    },
    amber: {
        dot: 'bg-amber-500',
        text: 'text-amber-600 dark:text-amber-300',
        soft: 'bg-amber-500/10',
        border: 'border-amber-500/30',
        bar: 'amber',
    },
    rose: {
        dot: 'bg-rose-500',
        text: 'text-rose-600 dark:text-rose-300',
        soft: 'bg-rose-500/10',
        border: 'border-rose-500/30',
        bar: 'rose',
    },
    sky: {
        dot: 'bg-sky-500',
        text: 'text-sky-600 dark:text-sky-300',
        soft: 'bg-sky-500/10',
        border: 'border-sky-500/30',
        bar: 'sky',
    },
    violet: {
        dot: 'bg-violet-500',
        text: 'text-violet-600 dark:text-violet-300',
        soft: 'bg-violet-500/10',
        border: 'border-violet-500/30',
        bar: 'violet',
    },
};

export const projectStatusClasses: Record<ProjectStatus, string> = {
    planning: 'border-sky-500/30 bg-sky-500/10 text-sky-700 dark:text-sky-300',
    active: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    paused: 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300',
    completed:
        'border-slate-500/30 bg-slate-500/10 text-slate-600 dark:text-slate-300',
};

export const projectStatusLabel: Record<ProjectStatus, string> = {
    planning: 'Planning',
    active: 'Active',
    paused: 'Paused',
    completed: 'Completed',
};

export const taskStatusClasses: Record<TaskStatus, string> = {
    todo: 'border-slate-500/30 bg-slate-500/10 text-slate-600 dark:text-slate-300',
    in_progress:
        'border-indigo-500/30 bg-indigo-500/10 text-indigo-700 dark:text-indigo-300',
    done: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
};

export const taskStatusLabel: Record<TaskStatus, string> = {
    todo: 'To do',
    in_progress: 'In progress',
    done: 'Done',
};

export const taskPriorityClasses: Record<TaskPriority, string> = {
    low: 'bg-slate-400',
    medium: 'bg-amber-500',
    high: 'bg-rose-500',
};

export const taskPriorityLabel: Record<TaskPriority, string> = {
    low: 'Low',
    medium: 'Medium',
    high: 'High',
};

export const learningStatusClasses: Record<LearningStatus, string> = {
    planned:
        'border-slate-500/30 bg-slate-500/10 text-slate-600 dark:text-slate-300',
    in_progress:
        'border-indigo-500/30 bg-indigo-500/10 text-indigo-700 dark:text-indigo-300',
    completed:
        'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
};

export const learningStatusLabel: Record<LearningStatus, string> = {
    planned: 'Planned',
    in_progress: 'In progress',
    completed: 'Completed',
};

export const noteColorClasses: Record<
    NoteColor,
    { card: string; dot: string }
> = {
    slate: {
        card: 'border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900/50',
        dot: 'bg-slate-400',
    },
    amber: {
        card: 'border-amber-200 bg-amber-50 dark:border-amber-900/60 dark:bg-amber-950/30',
        dot: 'bg-amber-400',
    },
    emerald: {
        card: 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/60 dark:bg-emerald-950/30',
        dot: 'bg-emerald-400',
    },
    sky: {
        card: 'border-sky-200 bg-sky-50 dark:border-sky-900/60 dark:bg-sky-950/30',
        dot: 'bg-sky-400',
    },
    violet: {
        card: 'border-violet-200 bg-violet-50 dark:border-violet-900/60 dark:bg-violet-950/30',
        dot: 'bg-violet-400',
    },
    rose: {
        card: 'border-rose-200 bg-rose-50 dark:border-rose-900/60 dark:bg-rose-950/30',
        dot: 'bg-rose-400',
    },
};

export const noteColorOptions: { value: NoteColor; label: string }[] = [
    { value: 'slate', label: 'Slate' },
    { value: 'amber', label: 'Amber' },
    { value: 'emerald', label: 'Emerald' },
    { value: 'sky', label: 'Sky' },
    { value: 'violet', label: 'Violet' },
    { value: 'rose', label: 'Rose' },
];

export const careerTypeClasses: Record<CareerType, string> = {
    goal: 'border-indigo-500/30 bg-indigo-500/10 text-indigo-700 dark:text-indigo-300',
    achievement:
        'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300',
    application:
        'border-sky-500/30 bg-sky-500/10 text-sky-700 dark:text-sky-300',
    certification:
        'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
};

export const careerTypeLabel: Record<CareerType, string> = {
    goal: 'Goal',
    achievement: 'Achievement',
    application: 'Application',
    certification: 'Certification',
};

export const careerStatusLabel: Record<CareerStatus, string> = {
    planned: 'Planned',
    in_progress: 'In progress',
    achieved: 'Achieved',
    archived: 'Archived',
};

export const careerStatusClasses: Record<CareerStatus, string> = {
    planned:
        'border-slate-500/30 bg-slate-500/10 text-slate-600 dark:text-slate-300',
    in_progress:
        'border-indigo-500/30 bg-indigo-500/10 text-indigo-700 dark:text-indigo-300',
    achieved:
        'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    archived:
        'border-slate-500/30 bg-slate-500/10 text-slate-500 dark:text-slate-400',
};
