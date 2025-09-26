import { Mark } from '@tiptap/core'

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
        return ['span', HTMLAttributes, 0]
    },

})