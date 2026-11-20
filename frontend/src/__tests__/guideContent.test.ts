import { describe, it, expect, vi } from 'vitest';
import { getGuideContent } from '../app/guide/content';
import { openAssistantFromGuide, subscribeOpenAssistant } from '../app/utils/assistantBridge';

describe('guide content and assistant bridge', () => {
  it('provides unique checklist keys for every role including teacher', () => {
    for (const role of ['director', 'secretary', 'teacher'] as const) {
      const content = getGuideContent(role);
      const keys = content.checklist.map((item) => item.key);
      expect(new Set(keys).size).toBe(keys.length);
      expect(content.workflows.length).toBeGreaterThan(0);
      expect(content.assistantByTab.length).toBeGreaterThan(0);
    }
  });

  it('covers dashboard, reports, daycare, and schoolcalendar tours for director', () => {
    const director = getGuideContent('director');
    const tourTabs = director.tours.map((t) => t.tab);
    expect(tourTabs).toContain('dashboard');
    expect(tourTabs).toContain('reports');
    expect(tourTabs).toContain('daycare');
    expect(tourTabs).toContain('schoolcalendar');
    expect(tourTabs).toContain('payments');
  });

  it('filters secretary workflows, tours, and assistant suggestions by permissions', () => {
    const limited = getGuideContent('secretary', ['students', 'attendance']);
    const allowedTabs = new Set(limited.tours.map((t) => t.tab));
    expect(allowedTabs.has('students')).toBe(true);
    expect(allowedTabs.has('attendance')).toBe(true);
    expect(allowedTabs.has('payments')).toBe(false);
    expect(limited.assistantByTab.some((a) => a.tab === 'payments')).toBe(false);
    expect(limited.workflows.every((wf) => wf.steps.every((s) => s.targetTab !== 'payments'))).toBe(true);
  });

  it('dispatches and receives contextual open-assistant events', () => {
    const handler = vi.fn();
    const unsubscribe = subscribeOpenAssistant(handler);

    openAssistantFromGuide({
      prompt: 'Comment fonctionne la règle des 4 et 7 séances ?',
      autoSend: true,
      contextLabel: 'Paiements',
      roleMode: 'general',
    });

    expect(handler).toHaveBeenCalledTimes(1);
    expect(handler).toHaveBeenCalledWith(
      expect.objectContaining({
        contextLabel: 'Paiements',
        autoSend: true,
      })
    );

    unsubscribe();
  });
});
