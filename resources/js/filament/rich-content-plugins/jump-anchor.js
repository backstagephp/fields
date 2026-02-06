import { Mark } from '@tiptap/core'
import { Plugin, PluginKey } from '@tiptap/pm/state'

export default Mark.create({
    name: 'jumpAnchor',

    addOptions() {
        return {
            HTMLAttributes: {},
        }
    },

    group: 'textStyle',

    priority: 1000,

    excludes: '',

    inclusive: false,

            addAttributes() {
                return {
                    'anchorId': {
                        default: null,
                        parseHTML: element => element.getAttribute('id'),
                        renderHTML: attributes => {
                            if (!attributes['anchorId']) {
                                return {}
                            }
                            
                            return {
                                'id': attributes['anchorId']
                            }
                        },
                    },
                }
            },

    addCommands() {
        return {
            setJumpAnchor:
                (attributes) =>
                ({ commands, state }) => {
                    return commands.setMark(this.name, attributes)
                },
            unsetJumpAnchor:
                () =>
                ({ chain }) => {
                    return chain()
                        .extendMarkRange(this.name)
                        .unsetMark(this.name)
                        .run()
                },
            toggleJumpAnchor:
                (attributes) =>
                ({ commands }) => {
                    return commands.toggleMark(this.name, attributes)
                },
        }
    },

            parseHTML() {
                return [
                    {
                        tag: 'span[id]',
                        getAttrs: element => {
                            const id = element.getAttribute('id')
                            return id ? { 
                                'anchorId': id
                            } : false
                        },
                    },
                ]
            },

    parseMarkdown() {
        return {
            // This ensures the mark is preserved when parsing markdown
            match: (node) => node.type === 'text' && node.marks?.some(mark => mark.type === 'jumpAnchor'),
            runner: (state, node, type) => {
                const mark = node.marks.find(mark => mark.type === 'jumpAnchor')
                if (mark) {
                    state.openMark(type.create(mark.attrs))
                    state.addText(node.text)
                    state.closeMark(type)
                } else {
                    state.addText(node.text)
                }
            }
        }
    },

    renderHTML({ HTMLAttributes, mark }) {
        return ['span', { ...HTMLAttributes, class: 'jump-anchor' }, 0]
    },

    addProseMirrorPlugins() {
        const markType = this.type

        return [
            new Plugin({
                key: new PluginKey('externalLinkClick'),
                props: {
                    handleClick(view, pos, event) {
                        const link = event.target.closest('a[target="_blank"]')
                        if (!link) {
                            return false
                        }

                        const range = document.createRange()
                        range.selectNodeContents(link)
                        const textRect = range.getBoundingClientRect()

                        if (event.clientX <= textRect.right) {
                            return false
                        }

                        const { doc, schema } = view.state
                        const linkMarkType = schema.marks.link
                        if (!linkMarkType) {
                            return false
                        }

                        const $pos = doc.resolve(pos)
                        const parent = $pos.parent
                        const base = $pos.start()

                        let start = null
                        let end = null

                        parent.forEach((child, offset) => {
                            const childFrom = base + offset
                            const childTo = childFrom + child.nodeSize

                            if (child.marks.some(m => m.type === linkMarkType) && pos >= childFrom && pos <= childTo) {
                                start = childFrom
                                end = childTo

                                // Expand to cover the full contiguous link mark
                                parent.forEach((sibling, sOffset) => {
                                    const sFrom = base + sOffset
                                    const sTo = sFrom + sibling.nodeSize
                                    if (!sibling.marks.some(m => m.type === linkMarkType)) return
                                    if (sFrom < start && sTo >= start) start = sFrom
                                    if (sFrom <= end && sTo > end) end = sTo
                                })
                            }
                        })

                        if (start !== null) {
                            const { tr } = view.state
                            view.dispatch(
                                tr.setSelection(
                                    view.state.selection.constructor.create(doc, start, end),
                                ),
                            )
                        }

                        return true
                    },
                },
            }),
            new Plugin({
                key: new PluginKey('jumpAnchorClick'),
                props: {
                    handleClick(view, pos, event) {
                        const anchor = event.target.closest('.jump-anchor')
                        if (!anchor) {
                            return false
                        }

                        // Only act when clicking the ::after indicator,
                        // not the text itself.
                        const range = document.createRange()
                        range.selectNodeContents(anchor)
                        const textRect = range.getBoundingClientRect()

                        if (event.clientX <= textRect.right) {
                            return false
                        }

                        const { doc } = view.state
                        const $pos = doc.resolve(pos)
                        const node = $pos.parent

                        let from = $pos.start()
                        let to = from

                        // Walk through the parent node's children to find the
                        // range of text covered by this jump anchor mark.
                        node.forEach((child, offset) => {
                            const childFrom = from + offset
                            const childTo = childFrom + child.nodeSize

                            if (
                                child.marks.some(m => m.type === markType) &&
                                pos >= childFrom &&
                                pos <= childTo
                            ) {
                                // Expand selection to cover adjacent nodes
                                // with the same mark (the full anchor range).
                                let start = childFrom
                                let end = childTo

                                // Expand backwards
                                node.forEach((sibling, sOffset) => {
                                    const sFrom = from + sOffset
                                    if (
                                        sFrom < childFrom &&
                                        sibling.marks.some(m => m.type === markType)
                                    ) {
                                        // Check continuity — only expand if
                                        // adjacent to current start
                                        if (sFrom + sibling.nodeSize >= start) {
                                            start = sFrom
                                        }
                                    }
                                })

                                // Expand forwards
                                node.forEach((sibling, sOffset) => {
                                    const sFrom = from + sOffset
                                    const sTo = sFrom + sibling.nodeSize
                                    if (
                                        sFrom >= childTo &&
                                        sibling.marks.some(m => m.type === markType)
                                    ) {
                                        if (sFrom <= end) {
                                            end = sTo
                                        }
                                    }
                                })

                                const { tr } = view.state
                                view.dispatch(
                                    tr.setSelection(
                                        view.state.selection.constructor.create(
                                            doc,
                                            start,
                                            end,
                                        ),
                                    ),
                                )
                            }
                        })

                        return true
                    },
                },
            }),
        ]
    },
})