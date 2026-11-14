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

export type Subtask = {
    id: string;
    title: string;
    completed: boolean;
};

export type CalendarEventType =
    | 'sprint'
    | 'meeting'
    | 'release'
    | 'deep_work'
    | 'deadline'
    | 'personal';

export type CalendarEvent = {
    id: number;
    project_id: number | null;
    title: string;
    description: string | null;
    type: CalendarEventType;
    color: ProjectColor;
    starts_at: string;
    ends_at: string | null;
    start_time: string | null;
    end_time: string | null;
    location: string | null;
    is_all_day: boolean;
    project?: Pick<Project, 'id' | 'name' | 'color'> | null;
    tasks?: Task[];
    created_at: string;
    updated_at: string;
};

export type Task = {
    id: number;
    project_id: number | null;
    calendar_event_id?: number | null;
    title: string;
    description: string | null;
    subtasks?: Subtask[] | null;
    status: TaskStatus;
    priority: TaskPriority;
    due_date: string | null;
    completed_at: string | null;
    project?: Pick<Project, 'id' | 'name' | 'color'> | null;
    event?: Pick<CalendarEvent, 'id' | 'title' | 'color' | 'starts_at'> | null;
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
