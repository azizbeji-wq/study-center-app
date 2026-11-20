export type AssistantRoleMode = 'general' | 'pedagogic' | 'management' | 'communication';

export interface OpenAssistantPayload {
  prompt?: string;
  autoSend?: boolean;
  contextLabel?: string;
  roleMode?: AssistantRoleMode;
  suggestedPrompts?: string[];
}

const OPEN_ASSISTANT_EVENT = 'studycenter:open-assistant';

export function openAssistantFromGuide(payload: OpenAssistantPayload): void {
  window.dispatchEvent(
    new CustomEvent<OpenAssistantPayload>(OPEN_ASSISTANT_EVENT, { detail: payload })
  );
}

export function subscribeOpenAssistant(
  handler: (payload: OpenAssistantPayload) => void
): () => void {
  const listener = (event: Event) => {
    const customEvent = event as CustomEvent<OpenAssistantPayload>;
    if (customEvent.detail) {
      handler(customEvent.detail);
    }
  };
  window.addEventListener(OPEN_ASSISTANT_EVENT, listener);
  return () => {
    window.removeEventListener(OPEN_ASSISTANT_EVENT, listener);
  };
}
