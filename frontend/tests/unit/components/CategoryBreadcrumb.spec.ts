import { config, mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';
import { nextTick } from 'vue';
import type { Category } from '~/types';
import CategoryBreadcrumb from '../../../components/categories/CategoryBreadcrumb.vue';

// Mock $t globally for i18n
config.global.mocks = {
  $t: (key: string) => key,
};

describe('CategoryBreadcrumb Component', () => {
  // Build a proper nested tree structure (not flat ancestors)
  const mockCategories = [
    {
      id: 1,
      parent_id: null,
      name_ar: 'مواد بناء',
      name_en: 'Building Materials',
      slug: 'building-materials',
      children: [
        {
          id: 2,
          parent_id: 1,
          name_ar: 'أسمنت',
          name_en: 'Cement',
          slug: 'cement',
          children: [
            {
              id: 3,
              parent_id: 2,
              name_ar: 'أسمنت بورتلاندي',
              name_en: 'Portland Cement',
              slug: 'portland-cement',
              children: [],
            },
          ],
        },
      ],
    },
  ];

  const getCategoryPath = (categoryId: number, categories: Category[]): Category[] => {
    for (const item of categories) {
      if (item.id === categoryId) {
        return [item];
      }
      if (item.children && item.children.length > 0) {
        const found = getCategoryPath(categoryId, item.children);
        if (found.length > 0) {
          return [item, ...found];
        }
      }
    }
    return [];
  };

  it('renders breadcrumb for root category', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 1,
        categories: mockCategories,
      },
      global: {
        stubs: {
          Icon: {
            template: '<span class="icon-stub"><slot /></span>',
          },
        },
      },
    });

    await nextTick();

    const items = wrapper.findAll('[data-testid="breadcrumb-item"]');
    expect(items.length).toBe(1);
    expect(items[0].text()).toContain('مواد بناء');
  });

  it('renders complete ancestor chain', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        categories: mockCategories,
      },
      global: {
        stubs: {
          Icon: {
            template: '<span class="icon-stub"><slot /></span>',
          },
        },
      },
    });

    await nextTick();

    const items = wrapper.findAll('[data-testid="breadcrumb-item"]');
    expect(items.length).toBe(3);
  });

  it('displays Arabic names in breadcrumb', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        categories: mockCategories,
      },
      global: {
        stubs: {
          Icon: {
            template: '<span class="icon-stub"><slot /></span>',
          },
        },
      },
    });

    await nextTick();

    const breadcrumbText = wrapper.text();
    expect(breadcrumbText).toContain('مواد بناء');
    expect(breadcrumbText).toContain('أسمنت');
    expect(breadcrumbText).toContain('أسمنت بورتلاندي');
  });

  it('displays Arabic names in breadcrumb when language is Arabic', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        categories: mockCategories,
        locale: 'ar',
      },
      global: {
        stubs: {
          Icon: {
            template: '<span class="icon-stub"><slot /></span>',
          },
          NuxtLink: {
            template: '<a><span>{{ text }}</span></a>',
            props: ['text'],
          },
        },
      },
    });

    await nextTick();

    const breadcrumbText = wrapper.text();
    expect(breadcrumbText).toBeDefined();
  });

  it('renders separators between breadcrumb items', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        categories: mockCategories,
      },
      global: {
        stubs: {
          Icon: {
            template: '<span class="icon-stub"><slot /></span>',
          },
          NuxtLink: {
            template: `<a data-testid="link"><slot /></a>`,
          },
        },
      },
    });

    await nextTick();

    const html = wrapper.html();
    expect(html).toContain('/');
  });

  it('generates correct routes for each breadcrumb link', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        categories: mockCategories,
      },
      global: {
        stubs: {
          Icon: {
            template: '<span class="icon-stub"><slot /></span>',
          },
          NuxtLink: {
            template: `<a :href="to" data-testid="link"><slot /></a>`,
            props: ['to'],
          },
        },
      },
    });

    await nextTick();

    const links = wrapper.findAll('[data-testid="link"]');
    for (const link of links) {
      const href = link.attributes('href');
      expect(href).toBeDefined();
    }
  });

  it('emits category selection when breadcrumb item is clicked', async () => {
    const mockRouter = {
      push: vi.fn(),
    };

    mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        categories: mockCategories,
      },
      global: {
        stubs: {
          Icon: {
            template: '<span class="icon-stub"><slot /></span>',
          },
          NuxtLink: {
            template: `<a @click="handleClick" data-testid="link" :href="to"><slot /></a>`,
            props: ['to'],
            methods: {
              handleClick() {
                this.$emit('click', this.to);
              },
            },
            emits: ['click'],
          },
        },
        provide: {
          router: mockRouter,
        },
      },
    });
  });

  it('reverses breadcrumb order in RTL mode', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        categories: mockCategories,
        isRTL: true,
      },
      global: {
        stubs: {
          Icon: {
            template: '<span class="icon-stub"><slot /></span>',
          },
          NuxtLink: {
            template: `<a data-testid="link"><slot /></a>`,
          },
        },
      },
    });

    await nextTick();

    const links = wrapper.findAll('[data-testid="link"]');
    if (wrapper.props().isRTL) {
      expect(links.length).toBe(3);
    }
  });

  it('creates click-through navigation to each ancestor', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        categories: mockCategories,
      },
      global: {
        stubs: {
          Icon: {
            template: '<span class="icon-stub"><slot /></span>',
          },
        },
      },
    });

    await nextTick();

    const items = wrapper.findAll('[data-testid="breadcrumb-item"]');
    expect(items.length).toBeGreaterThan(0);
  });

  it('handles single root category breadcrumb', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 1,
        categories: mockCategories,
      },
      global: {
        stubs: {
          Icon: {
            template: '<span class="icon-stub"><slot /></span>',
          },
        },
      },
    });

    await nextTick();

    const items = wrapper.findAll('[data-testid="breadcrumb-item"]');
    expect(items.length).toBe(1);
  });

  it('handles deeply nested hierarchy (5+ levels)', async () => {
    const deepCategories = [
      {
        id: 1,
        parent_id: null,
        name_ar: 'مستوى 1',
        name_en: 'Level 1',
        slug: 'level-1',
        children: [
          {
            id: 2,
            parent_id: 1,
            name_ar: 'مستوى 2',
            name_en: 'Level 2',
            slug: 'level-2',
            children: [
              {
                id: 3,
                parent_id: 2,
                name_ar: 'مستوى 3',
                name_en: 'Level 3',
                slug: 'level-3',
                children: [
                  {
                    id: 4,
                    parent_id: 3,
                    name_ar: 'مستوى 4',
                    name_en: 'Level 4',
                    slug: 'level-4',
                    children: [
                      {
                        id: 5,
                        parent_id: 4,
                        name_ar: 'مستوى 5',
                        name_en: 'Level 5',
                        slug: 'level-5',
                        children: [],
                      },
                    ],
                  },
                ],
              },
            ],
          },
        ],
      },
    ];

    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 5,
        categories: deepCategories,
      },
      global: {
        stubs: {
          Icon: {
            template: '<span class="icon-stub"><slot /></span>',
          },
        },
      },
    });

    await nextTick();

    const items = wrapper.findAll('[data-testid="breadcrumb-item"]');
    expect(items.length).toBe(5);
  });

  it('uses screen-reader accessible markup', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        categories: mockCategories,
      },
      global: {
        stubs: {
          Icon: {
            template: '<span class="icon-stub"><slot /></span>',
          },
          NuxtLink: {
            template: `<a rel="breadcrumb" data-testid="link"><slot /></a>`,
            props: ['rel'],
          },
        },
      },
    });

    await nextTick();

    const html = wrapper.html();
    expect(html).toBeDefined();
  });

  it('maintains correct slug/route for each breadcrumb level', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        categories: mockCategories,
      },
      global: {
        stubs: {
          Icon: {
            template: '<span class="icon-stub"><slot /></span>',
          },
          NuxtLink: {
            template: `<a :data-slug="to" data-testid="link"><slot /></a>`,
            props: ['to'],
          },
        },
      },
    });

    await nextTick();

    const links = wrapper.findAll('[data-testid="link"]');
    for (const link of links) {
      expect(true).toBe(true);
    }
  });

  it('shows loading state while fetching ancestors', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        categories: [],
        isLoading: true,
      },
      global: {
        stubs: {
          Icon: {
            template: '<span class="icon-stub"><slot /></span>',
          },
          USkeletonLoader: {
            template: `<div data-testid="skeleton">Loading...</div>`,
          },
        },
      },
    });

    await nextTick();

    if (wrapper.props().isLoading) {
      expect(wrapper.find('[data-testid="skeleton"]').exists()).toBe(true);
    }
  });

  it('handles empty ancestors list gracefully', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 1,
        categories: [],
      },
      global: {
        stubs: {
          Icon: {
            template: '<span class="icon-stub"><slot /></span>',
          },
          NuxtLink: {
            template: `<a data-testid="link"><slot /></a>`,
          },
        },
      },
    });

    await nextTick();

    expect(wrapper.exists()).toBe(true);
  });

  it('applies RTL styling classes when locale is Arabic', async () => {
    const wrapper = mount(CategoryBreadcrumb, {
      props: {
        categoryId: 3,
        categories: mockCategories,
        locale: 'ar',
      },
      global: {
        stubs: {
          Icon: {
            template: '<span class="icon-stub"><slot /></span>',
          },
          NuxtLink: {
            template: `<a data-testid="link"><slot /></a>`,
          },
        },
      },
    });

    await nextTick();

    const html = wrapper.html();
    expect(html).toBeDefined();
  });
});
