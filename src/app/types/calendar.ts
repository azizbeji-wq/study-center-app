export interface School {
  id: number;
  name: string;
  address?: string | null;
  city?: string | null;
  classes_count?: number;
}

export interface CalendarImport {
  id: number;
  file_name: string;
  file_type: string | null;
  created_at: string;
  school_id: number;
  school_name: string;
  level: number;
  section: string;
}

export interface SectionOption {
  value: string;
  label: string;
}
