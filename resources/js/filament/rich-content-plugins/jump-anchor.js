import { Extension } from '@tiptap/core'
import { Plugin, PluginKey } from '@tiptap/pm/state'
import { Decoration, DecorationSet } from '@tiptap/pm/view'

const JumpAnchorPluginKey = new PluginKey('jumpAnchor')

export default Extension.create({
    name: 'jumpAnchor',

    addOptions() {
        return {
            types: ['textStyle'],
            HTMLAttributes: {},
        }
    },

    addAttributes() {
        return {
            'data-anchor-id': {
                default: null,
                parseHTML: element => element.getAttribute('data-anchor-id'),
                renderHTML: attributes => {
                    if (!attributes['data-anchor-id']) {
                        return {}
                    }
                    return {
                        'data-anchor-id': attributes['data-anchor-id'],
                    }
                },
            },
        }
    },

    addCommands() {
        return {
            setJumpAnchor:
                (attributes) =>
                ({ commands }) => {
                    return commands.setMark(this.name, attributes)
                },
            unsetJumpAnchor:
                () =>
                ({ commands }) => {
                    return commands.unsetMark(this.name)
                },
            toggleJumpAnchor:
                (attributes) =>
                ({ commands }) => {
                    return commands.toggleMark(this.name, attributes)
                },
        }
    },

    addProseMirrorPlugins() {
        return [
            new Plugin({
                key: JumpAnchorPluginKey,
                props: {
                    decorations: (state) => {
                        const decorations = []
                        const { doc, selection } = state

                        doc.descendants((node, pos) => {
                            if (node.isText && node.marks) {
                                node.marks.forEach((mark) => {
                                    if (mark.type.name === this.name && mark.attrs['data-anchor-id']) {
                                        const decoration = Decoration.inline(pos, pos + node.nodeSize, {
                                            class: 'jump-anchor',
                                            'data-anchor-id': mark.attrs['data-anchor-id'],
                                            title: `Jump to: ${mark.attrs['data-anchor-id']}`,
                                        })
                                        decorations.push(decoration)
                                    }
                                })
                            }
                        })

                        return DecorationSet.create(doc, decorations)
                    },
                },
            }),
        ]
    },

    parseHTML() {
        return [
            {
                tag: 'span[data-anchor-id]',
            },
        ]
    },

    renderHTML({ HTMLAttributes }) {
        return ['span', HTMLAttributes, 0]
    },
})
