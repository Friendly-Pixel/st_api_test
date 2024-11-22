// https://stackoverflow.com/questions/69327990/how-can-i-make-one-property-non-optional-in-a-typescript-type
type WithRequired<T, K extends keyof T> = T & { [P in K]-?: T[P] };

/* https://stackoverflow.com/questions/75691890/make-certain-nested-properties-required-in-a-typescript-type */

type DeepPartialExcept<
  T extends object,
  K extends NestedKeyOf<T>,
> = UnionToIntersection<PartialExceptUnion<T, K> | DeepPartial<T>>;

type UnionToIntersection<U> = (U extends U ? (arg: U) => void : never) extends (
  arg: infer I
) => void
  ? I
  : never;

type PartialExceptUnion<
  T,
  K extends string,
> = K extends `${infer KFirst extends Extract<keyof T, string>}.${infer KRest extends string}`
  ? {
      [Key in KFirst]: Key extends keyof T
        ? PartialExceptUnion<T[Key], KRest>
        : never;
    }
  : K extends keyof T
    ? { [Key in K]: NonNullable<T[Key]> }
    : never;

type DeepPartial<T> = T extends object
  ? {
      [P in keyof T]?: DeepPartial<T[P]>;
    }
  : T;

type NestedKeyOf<T extends object> = {
  [P in keyof T & (string | number)]: T[P] extends object
    ? `${P}` | `${P}.${NestedKeyOf<T[P]>}`
    : `${P}`;
}[keyof T & (string | number)];
