import AdminLayout from '@/Components/Layout/AdminLayout';
import { router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import type { LandingPageSectionType } from '@/Types/branding';

interface LandingPageEditorProps {
    sections: LandingPageSectionType[];
}

const SECTION_TYPES = [
    { value: 'hero', label: 'Hero' },
    { value: 'about', label: 'About' },
    { value: 'featured_courses', label: 'Featured Courses' },
    { value: 'testimonials', label: 'Testimonials' },
    { value: 'cta', label: 'Call to Action' },
] as const;

const SECTION_TYPE_LABELS: Record<string, string> = {
    hero: 'Hero',
    about: 'About',
    featured_courses: 'Featured Courses',
    testimonials: 'Testimonials',
    cta: 'Call to Action',
};

export default function LandingPageEditor({ sections }: LandingPageEditorProps) {
    const [showAddForm, setShowAddForm] = useState(false);
    const [editingSectionId, setEditingSectionId] = useState<number | null>(null);

    function handleToggleVisibility(section: LandingPageSectionType) {
        router.put(
            `/admin/branding/landing-sections/${section.id}`,
            {
                section_type: section.sectionType,
                title: section.title ?? '',
                sort_order: section.sortOrder,
                is_visible: !section.isVisible,
            },
            { preserveScroll: true },
        );
    }

    function handleDelete(sectionId: number) {
        if (!confirm('Are you sure you want to delete this section?')) return;
        router.delete(`/admin/branding/landing-sections/${sectionId}`, {
            preserveScroll: true,
        });
    }

    function handleMoveUp(index: number) {
        if (index <= 0) return;
        const current = sections[index];
        const above = sections[index - 1];

        // Swap sort orders
        router.put(
            `/admin/branding/landing-sections/${current.id}`,
            {
                section_type: current.sectionType,
                title: current.title ?? '',
                sort_order: above.sortOrder,
                is_visible: current.isVisible,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.put(
                        `/admin/branding/landing-sections/${above.id}`,
                        {
                            section_type: above.sectionType,
                            title: above.title ?? '',
                            sort_order: current.sortOrder,
                            is_visible: above.isVisible,
                        },
                        { preserveScroll: true },
                    );
                },
            },
        );
    }

    function handleMoveDown(index: number) {
        if (index >= sections.length - 1) return;
        const current = sections[index];
        const below = sections[index + 1];

        router.put(
            `/admin/branding/landing-sections/${current.id}`,
            {
                section_type: current.sectionType,
                title: current.title ?? '',
                sort_order: below.sortOrder,
                is_visible: current.isVisible,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.put(
                        `/admin/branding/landing-sections/${below.id}`,
                        {
                            section_type: below.sectionType,
                            title: below.title ?? '',
                            sort_order: current.sortOrder,
                            is_visible: below.isVisible,
                        },
                        { preserveScroll: true },
                    );
                },
            },
        );
    }

    return (
        <AdminLayout>
            <div className="mx-auto max-w-4xl">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">Landing Page Editor</h1>
                    <button
                        onClick={() => {
                            setShowAddForm(!showAddForm);
                            setEditingSectionId(null);
                        }}
                        className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        {showAddForm ? 'Cancel' : '+ Add Section'}
                    </button>
                </div>

                {showAddForm && (
                    <div className="mb-6">
                        <SectionForm
                            nextSortOrder={sections.length > 0 ? Math.max(...sections.map((s) => s.sortOrder)) + 1 : 0}
                            onCancel={() => setShowAddForm(false)}
                        />
                    </div>
                )}

                {sections.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center">
                        <p className="text-sm text-gray-500">No landing page sections yet. Add your first section to get started.</p>
                    </div>
                ) : (
                    <ul className="space-y-3" role="list" aria-label="Landing page sections">
                        {sections.map((section, index) => (
                            <li key={section.id}>
                                {editingSectionId === section.id ? (
                                    <SectionForm
                                        section={section}
                                        onCancel={() => setEditingSectionId(null)}
                                    />
                                ) : (
                                    <div
                                        className={`flex items-center gap-4 rounded-xl border bg-white p-4 shadow-sm ${
                                            section.isVisible ? 'border-gray-200' : 'border-gray-200 opacity-60'
                                        }`}
                                    >
                                        {/* Reorder buttons */}
                                        <div className="flex flex-col gap-1">
                                            <button
                                                onClick={() => handleMoveUp(index)}
                                                disabled={index === 0}
                                                className="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-30"
                                                aria-label={`Move ${section.title ?? section.sectionType} up`}
                                            >
                                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 15l7-7 7 7" />
                                                </svg>
                                            </button>
                                            <button
                                                onClick={() => handleMoveDown(index)}
                                                disabled={index === sections.length - 1}
                                                className="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-30"
                                                aria-label={`Move ${section.title ?? section.sectionType} down`}
                                            >
                                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </div>

                                        {/* Section info */}
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center gap-2">
                                                <span className="inline-flex rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700">
                                                    {SECTION_TYPE_LABELS[section.sectionType] ?? section.sectionType}
                                                </span>
                                                {!section.isVisible && (
                                                    <span className="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500">
                                                        Hidden
                                                    </span>
                                                )}
                                            </div>
                                            <p className="mt-1 truncate text-sm font-medium text-gray-900">
                                                {section.title || '(No title)'}
                                            </p>
                                            {section.subtitle && (
                                                <p className="truncate text-xs text-gray-500">{section.subtitle}</p>
                                            )}
                                        </div>

                                        {/* Sort order indicator */}
                                        <span className="text-xs text-gray-400">#{section.sortOrder}</span>

                                        {/* Actions */}
                                        <div className="flex items-center gap-2">
                                            <button
                                                onClick={() => handleToggleVisibility(section)}
                                                className={`relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 ${
                                                    section.isVisible ? 'bg-indigo-600' : 'bg-gray-200'
                                                }`}
                                                role="switch"
                                                aria-checked={section.isVisible}
                                                aria-label={`Toggle visibility for ${section.title ?? section.sectionType}`}
                                            >
                                                <span
                                                    className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition-transform ${
                                                        section.isVisible ? 'translate-x-5' : 'translate-x-0'
                                                    }`}
                                                />
                                            </button>

                                            <button
                                                onClick={() => {
                                                    setEditingSectionId(section.id);
                                                    setShowAddForm(false);
                                                }}
                                                className="rounded-lg px-3 py-1.5 text-sm font-medium text-indigo-600 hover:bg-indigo-50"
                                            >
                                                Edit
                                            </button>

                                            <button
                                                onClick={() => handleDelete(section.id)}
                                                className="rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </AdminLayout>
    );
}

/* ------------------------------------------------------------------ */
/* Section add/edit form                                               */
/* ------------------------------------------------------------------ */

interface SectionFormProps {
    section?: LandingPageSectionType;
    nextSortOrder?: number;
    onCancel: () => void;
}

function SectionForm({ section, nextSortOrder = 0, onCancel }: SectionFormProps) {
    const isEditing = !!section;

    const { data, setData, post, put, processing, errors } = useForm({
        section_type: section?.sectionType ?? 'hero',
        title: section?.title ?? '',
        subtitle: section?.subtitle ?? '',
        content: section?.content ?? '',
        image_url: section?.imageUrl ?? '',
        cta_text: section?.ctaText ?? '',
        cta_url: section?.ctaUrl ?? '',
        sort_order: section?.sortOrder ?? nextSortOrder,
        is_visible: section?.isVisible ?? true,
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        if (isEditing && section) {
            put(`/admin/branding/landing-sections/${section.id}`, {
                preserveScroll: true,
                onSuccess: () => onCancel(),
            });
        } else {
            post('/admin/branding/landing-sections', {
                preserveScroll: true,
                onSuccess: () => onCancel(),
            });
        }
    }

    return (
        <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <h2 className="mb-4 text-lg font-semibold text-gray-900">
                {isEditing ? 'Edit Section' : 'Add New Section'}
            </h2>
            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {/* Section Type */}
                    <div>
                        <label htmlFor="section_type" className="mb-1 block text-sm font-medium text-gray-700">
                            Section Type
                        </label>
                        <select
                            id="section_type"
                            value={data.section_type}
                            onChange={(e) => setData('section_type', e.target.value as typeof data.section_type)}
                            className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        >
                            {SECTION_TYPES.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </select>
                        {errors.section_type && (
                            <p className="mt-1 text-sm text-red-600" role="alert">{errors.section_type}</p>
                        )}
                    </div>

                    {/* Sort Order */}
                    <div>
                        <label htmlFor="sort_order" className="mb-1 block text-sm font-medium text-gray-700">
                            Sort Order
                        </label>
                        <input
                            id="sort_order"
                            type="number"
                            value={data.sort_order}
                            onChange={(e) => setData('sort_order', parseInt(e.target.value, 10) || 0)}
                            className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        />
                        {errors.sort_order && (
                            <p className="mt-1 text-sm text-red-600" role="alert">{errors.sort_order}</p>
                        )}
                    </div>
                </div>

                {/* Title */}
                <div>
                    <label htmlFor="section_title" className="mb-1 block text-sm font-medium text-gray-700">
                        Title
                    </label>
                    <input
                        id="section_title"
                        type="text"
                        maxLength={255}
                        value={data.title}
                        onChange={(e) => setData('title', e.target.value)}
                        className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    />
                    {errors.title && (
                        <p className="mt-1 text-sm text-red-600" role="alert">{errors.title}</p>
                    )}
                </div>

                {/* Subtitle */}
                <div>
                    <label htmlFor="section_subtitle" className="mb-1 block text-sm font-medium text-gray-700">
                        Subtitle
                    </label>
                    <input
                        id="section_subtitle"
                        type="text"
                        value={data.subtitle}
                        onChange={(e) => setData('subtitle', e.target.value)}
                        className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    />
                    {errors.subtitle && (
                        <p className="mt-1 text-sm text-red-600" role="alert">{errors.subtitle}</p>
                    )}
                </div>

                {/* Content */}
                <div>
                    <label htmlFor="section_content" className="mb-1 block text-sm font-medium text-gray-700">
                        Content
                    </label>
                    <textarea
                        id="section_content"
                        rows={3}
                        value={data.content}
                        onChange={(e) => setData('content', e.target.value)}
                        className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    />
                    {errors.content && (
                        <p className="mt-1 text-sm text-red-600" role="alert">{errors.content}</p>
                    )}
                </div>

                {/* Image URL */}
                <div>
                    <label htmlFor="section_image_url" className="mb-1 block text-sm font-medium text-gray-700">
                        Image URL
                    </label>
                    <input
                        id="section_image_url"
                        type="url"
                        maxLength={500}
                        value={data.image_url}
                        onChange={(e) => setData('image_url', e.target.value)}
                        placeholder="https://example.com/image.jpg"
                        className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                    />
                    {errors.image_url && (
                        <p className="mt-1 text-sm text-red-600" role="alert">{errors.image_url}</p>
                    )}
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {/* CTA Text */}
                    <div>
                        <label htmlFor="section_cta_text" className="mb-1 block text-sm font-medium text-gray-700">
                            CTA Button Text
                        </label>
                        <input
                            id="section_cta_text"
                            type="text"
                            maxLength={100}
                            value={data.cta_text}
                            onChange={(e) => setData('cta_text', e.target.value)}
                            placeholder="e.g. Get Started"
                            className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        />
                        {errors.cta_text && (
                            <p className="mt-1 text-sm text-red-600" role="alert">{errors.cta_text}</p>
                        )}
                    </div>

                    {/* CTA URL */}
                    <div>
                        <label htmlFor="section_cta_url" className="mb-1 block text-sm font-medium text-gray-700">
                            CTA Button URL
                        </label>
                        <input
                            id="section_cta_url"
                            type="url"
                            maxLength={500}
                            value={data.cta_url}
                            onChange={(e) => setData('cta_url', e.target.value)}
                            placeholder="https://example.com/signup"
                            className="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                        />
                        {errors.cta_url && (
                            <p className="mt-1 text-sm text-red-600" role="alert">{errors.cta_url}</p>
                        )}
                    </div>
                </div>

                {/* Visibility */}
                <div className="flex items-center gap-3">
                    <input
                        id="section_is_visible"
                        type="checkbox"
                        checked={data.is_visible}
                        onChange={(e) => setData('is_visible', e.target.checked)}
                        className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    <label htmlFor="section_is_visible" className="text-sm font-medium text-gray-700">
                        Visible on landing page
                    </label>
                </div>

                {/* Actions */}
                <div className="flex gap-3 pt-2">
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {processing ? 'Saving…' : isEditing ? 'Update Section' : 'Create Section'}
                    </button>
                    <button
                        type="button"
                        onClick={onCancel}
                        className="rounded-xl border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    );
}
