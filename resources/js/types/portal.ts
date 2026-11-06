export type ProjectStatus = 'planning' | 'active' | 'paused' | 'completed';

export type ProjectColor =
    | 'indigo'
    | 'emerald'
    | 'amber'
    | 'rose'
    | 'sky'
    | 'violet';

export type Project = {
    id: number;
    name: string;
    description: string | null;
    status: ProjectStatus;
    color: ProjectColor;
    tech_stack: string[] | null;
    repository_url: string | null;
    live_url: string | null;
    started_at: string | null;
    due_at: string | null;
    tasks_count?: number;
    completed_tasks_count?: number;
    created_at: string;
    updated_at: string;
};

export type TaskStatus = 'todo' | 'in_progress' | 'done';
export type TaskPriority = 'low' | 'medium' | 'high';

export type Task = {
    id: number;
    project_id: number | null;
    title: string;
    description: string | null;
    status: TaskStatus;
    priority: TaskPriority;
    due_date: string | null;
    completed_at: string | null;
    project?: Pick<Project, 'id' | 'name' | 'color'> | null;
    created_at: string;
    updated_at: string;
};

export type LearningStatus = 'planned' | 'in_progress' | 'completed';
export type LearningFormat = 'course' | 'book' | 'article' | 'video' | 'docs';

export type LearningResource = {
    id: number;
    title: string;
    provider: string | null;
    url: string | null;
    category: string;
    format: LearningFormat;
    status: LearningStatus;
    progress: number;
    notes: string | null;
    completed_at: string | null;
    created_at: string;
    updated_at: string;
};

export type NoteColor =
    | 'slate'
    | 'amber'
    | 'emerald'
    | 'sky'
    | 'violet'
    | 'rose';

export type Note = {
    id: number;
    title: string;
    body: string;
    tags: string[] | null;
    color: NoteColor;
    is_pinned: boolean;
    created_at: string;
    updated_at: string;
};

export type CareerType =
    | 'goal'
    | 'achievement'
    | 'application'
    | 'certification';
export type CareerStatus = 'planned' | 'in_progress' | 'achieved' | 'archived';

export type CareerEntry = {
    id: number;
    title: string;
    type: CareerType;
    organization: string | null;
    description: string | null;
    status: CareerStatus;
    target_date: string | null;
    completed_at: string | null;
    created_at: string;
    updated_at: string;
};
