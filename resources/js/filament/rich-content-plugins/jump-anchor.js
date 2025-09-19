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
                parseHTML: element => element.getAttribute('id') || element.getAttribute('data-anchor-id'),
                renderHTML: attributes => {
                    if (!attributes['anchorId']) {
                        return {}
                    }
                    
                    const result = {}
                    const attributeType = attributes['attributeType'] || 'id'
                    const customAttribute = attributes['customAttribute']
                    
                    if (attributeType === 'id') {
                        result['id'] = attributes['anchorId']
                        // Don't add data-anchor-id when using ID mode
                    } else if (attributeType === 'custom' && customAttribute) {
                        result[customAttribute] = attributes['anchorId']
                        // Don't add data-anchor-id when using custom attributes
                    }
                    
                    return result
                },
            },
            'attributeType': {
                default: 'id',
                parseHTML: element => {
                    if (element.hasAttribute('id')) return 'id'
                    return 'custom'
                },
                renderHTML: attributes => {
                    return {}
                },
            },
            'customAttribute': {
                default: null,
                parseHTML: element => {
                    // Find the first non-standard attribute
                    const attrs = element.attributes
                    for (let i = 0; i < attrs.length; i++) {
                        const attr = attrs[i]
                        if (attr.name !== 'id' && attr.name !== 'data-anchor-id' && attr.name !== 'class') {
                            return attr.name
                        }
                    }
                    return null
                },
                renderHTML: attributes => {
                    return {}
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
                        'anchorId': id,
                        'attributeType': 'id'
                    } : false
                },
            },
            {
                tag: 'span',
                getAttrs: element => {
                    // Check for any custom attribute that looks like an anchor
                    const attrs = element.attributes
                    for (let i = 0; i < attrs.length; i++) {
                        const attr = attrs[i]
                        if (attr.name.startsWith('data-') && attr.name !== 'data-anchor-id') {
                            return { 
                                'anchorId': attr.value,
                                'attributeType': 'custom',
                                'customAttribute': attr.name
                            }
                        }
                    }
                    return false
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